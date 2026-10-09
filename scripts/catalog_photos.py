"""Take product photos out of the price list saved as Excel and attach them to the catalog.

Usage: python3 scripts/catalog_photos.py price.xlsx
Export: Google Sheets → Файл → Скачать → Microsoft Excel (.xlsx).

A photo belongs to the row it sits in (floating pictures by their top-left cell,
«image in cell» pictures by their cell). The row's article number links it to a
product line in site/app/catalog.json; the first photo of a line becomes the card
photo, saved as site/assets/img/catalog/<slug>.webp.
"""
import io
import json
import os
import posixpath
import re
import sys
import zipfile
import xml.etree.ElementTree as ET

from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CATALOG = os.path.join(ROOT, 'site', 'app', 'catalog.json')
OUT_DIR = os.path.join(ROOT, 'site', 'assets', 'img', 'catalog')
NS = {
    'm': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
    'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
    'xdr': 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing',
    'a': 'http://schemas.openxmlformats.org/drawingml/2006/main',
    'rel': 'http://schemas.openxmlformats.org/package/2006/relationships',
    'rv': 'http://schemas.microsoft.com/office/spreadsheetml/2017/richdata',
}


def rels(z, part):
    """Relationship id → target path for one package part."""
    path = posixpath.join(posixpath.dirname(part), '_rels', posixpath.basename(part) + '.rels')
    if path not in z.namelist():
        return {}
    out = {}
    for r in ET.fromstring(z.read(path)).findall('rel:Relationship', NS):
        t = r.get('Target')
        out[r.get('Id')] = t.lstrip('/') if t.startswith('/') else posixpath.normpath(posixpath.join(posixpath.dirname(part), t))
    return out


def col_index(ref):
    letters = re.match(r'[A-Z]+', ref).group(0)
    n = 0
    for ch in letters:
        n = n * 26 + ord(ch) - 64
    return n - 1


def shared_strings(z):
    if 'xl/sharedStrings.xml' not in z.namelist():
        return []
    root = ET.fromstring(z.read('xl/sharedStrings.xml'))
    return [''.join(t.text or '' for t in si.iter('{%s}t' % NS['m'])) for si in root.findall('m:si', NS)]


def main(xlsx):
    z = zipfile.ZipFile(xlsx)
    wb = ET.fromstring(z.read('xl/workbook.xml'))
    wb_rels = rels(z, 'xl/workbook.xml')
    sheets = [(s.get('name'), wb_rels[s.get('{%s}id' % NS['r'])]) for s in wb.find('m:sheets', NS)]
    sheet = next((p for n, p in sheets if 'АЛМАДЕЗ' in n.upper()), sheets[0][1])
    strings = shared_strings(z)
    root = ET.fromstring(z.read(sheet))

    # row → article, and cells holding «image in cell» pictures (value metadata index)
    article, cell_images = {}, {}
    for c in root.iter('{%s}c' % NS['m']):
        ref = c.get('r')
        row = int(re.search(r'\d+', ref).group(0)) - 1
        v = c.find('m:v', NS)
        if col_index(ref) == 0:
            if c.get('t') == 'inlineStr':
                text = ''.join(t.text or '' for t in c.iter('{%s}t' % NS['m']))
            elif v is None:
                text = ''
            else:
                text = strings[int(v.text)] if c.get('t') == 's' else (v.text or '')
            if text.strip():
                article[row] = text.strip()
        if c.get('vm'):
            cell_images.setdefault(row, int(c.get('vm')) - 1)

    photos = {}  # row → image bytes
    sheet_rels = rels(z, sheet)
    for d in root.findall('m:drawing', NS):
        drawing = sheet_rels[d.get('{%s}id' % NS['r'])]
        d_rels = rels(z, drawing)
        for anchor in ET.fromstring(z.read(drawing)):
            frm = anchor.find('xdr:from', NS)
            blip = anchor.find('.//a:blip', NS)
            if frm is None or blip is None:
                continue
            row = int(frm.find('xdr:row', NS).text)
            photos.setdefault(row, z.read(d_rels[blip.get('{%s}embed' % NS['r'])]))

    if cell_images and 'xl/richData/rdrichvalue.xml' in z.namelist():
        # vm → rich value → index into richValueRel → image part
        values = [[v.text for v in rv.findall('rv:v', NS)] for rv in ET.fromstring(z.read('xl/richData/rdrichvalue.xml'))]
        rel_part = 'xl/richData/richValueRel.xml'
        rel_ids = [r.get('{%s}id' % NS['r']) for r in ET.fromstring(z.read(rel_part))]
        targets = rels(z, rel_part)
        for row, vm in cell_images.items():
            if vm < len(values) and values[vm]:
                idx = int(values[vm][0])
                if idx < len(rel_ids):
                    photos.setdefault(row, z.read(targets[rel_ids[idx]]))

    by_sku = {}
    for row, data in photos.items():
        if row in article:
            by_sku[article[row]] = data
    print('photos in sheet: %d, matched to articles: %d' % (len(photos), len(by_sku)))

    catalog = json.load(open(CATALOG, encoding='utf-8'))
    os.makedirs(OUT_DIR, exist_ok=True)
    done, missing = 0, []
    for g in catalog:
        for it in g['items']:
            data = next((by_sku[v['sku']] for v in it['variants'] if v['sku'] in by_sku), None)
            if data is None:
                missing.append(it['name'])
                continue
            im = Image.open(io.BytesIO(data))
            im = im.convert('RGBA') if im.mode in ('P', 'LA', 'RGBA') else im.convert('RGB')
            im.thumbnail((800, 800))
            im.save(os.path.join(OUT_DIR, it['slug'] + '.webp'), 'WEBP', quality=82)
            it['image'] = '/assets/img/catalog/%s.webp' % it['slug']
            done += 1
    with open(CATALOG, 'w', encoding='utf-8') as f:
        f.write(json.dumps(catalog, ensure_ascii=False, indent=4) + '\n')
    print('product lines with photo: %d' % done)
    if missing:
        print('without photo: ' + ', '.join(missing))


if __name__ == '__main__':
    main(sys.argv[1])
