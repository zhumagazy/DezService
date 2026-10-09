"""Take product photos out of the price list saved as Excel and attach them to the catalog.

Usage: python3 scripts/catalog_photos.py price.xlsx [more.xlsx ...]
Export: Google Sheets → Файл → Скачать → Microsoft Excel (.xlsx). A big sheet can be
split into several files, pass them all.

A photo belongs to the row it sits in (floating pictures by their top-left cell,
«image in cell» pictures by their cell); of several pictures in one row the sharpest
is kept. The row's article number links it to a size in site/app/catalog.json; every
size gets its photo (site/assets/img/catalog/<article>.webp), the first one with a
photo becomes the card photo of the line.
"""
import io
import json
import os
import posixpath
import re
import sys
import zipfile
import xml.etree.ElementTree as ET

from PIL import Image, ImageChops

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


def picture_row(frm, heights, default_h):
    """Row the top edge of a picture is in: its offset can carry it past the anchor row."""
    row = int(frm.find('xdr:row', NS).text)
    y = int(frm.find('xdr:rowOff', NS).text) + 2 * 12700  # 2 pt: a picture on a row border belongs below
    while y > heights.get(row, default_h):
        y -= heights.get(row, default_h)
        row += 1
    return row


def shared_strings(z):
    if 'xl/sharedStrings.xml' not in z.namelist():
        return []
    root = ET.fromstring(z.read('xl/sharedStrings.xml'))
    return [''.join(t.text or '' for t in si.iter('{%s}t' % NS['m'])) for si in root.findall('m:si', NS)]


def read_photos(xlsx):
    """Article → picture bytes for one workbook."""
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

    # row heights in EMU: a picture can start in one row and sit (by its offset) in the next ones
    fmt = root.find('m:sheetFormatPr', NS)
    default_h = float(fmt.get('defaultRowHeight', 15)) * 12700 if fmt is not None else 15 * 12700
    heights = {int(r.get('r')) - 1: float(r.get('ht')) * 12700 for r in root.iter('{%s}row' % NS['m']) if r.get('ht')}

    photos = {}  # row → [image bytes]
    sheet_rels = rels(z, sheet)
    for d in root.findall('m:drawing', NS):
        drawing = sheet_rels[d.get('{%s}id' % NS['r'])]
        d_rels = rels(z, drawing)
        for anchor in ET.fromstring(z.read(drawing)):
            frm = anchor.find('xdr:from', NS)
            blip = anchor.find('.//a:blip', NS)
            if frm is None or blip is None:
                continue
            row = picture_row(frm, heights, default_h)
            photos.setdefault(row, []).append(z.read(d_rels[blip.get('{%s}embed' % NS['r'])]))

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
                    photos.setdefault(row, []).append(z.read(targets[rel_ids[idx]]))

    by_sku = {}
    for row, pics in photos.items():
        if row in article:
            by_sku[article[row]] = max(pics, key=lambda b: (lambda im: im.size[0] * im.size[1])(Image.open(io.BytesIO(b))))
    print('%s: photos in %d rows, matched to articles: %d' % (os.path.basename(xlsx), len(photos), len(by_sku)))
    return by_sku


def clean(data):
    """White background, empty margins cut off, at most 800 px."""
    im = Image.open(io.BytesIO(data))
    if im.mode in ('P', 'LA', 'RGBA', 'PA'):
        im = im.convert('RGBA')
        bg = Image.new('RGB', im.size, (255, 255, 255))
        bg.paste(im, mask=im.split()[3])
        im = bg
    else:
        im = im.convert('RGB')
    diff = ImageChops.difference(im, Image.new('RGB', im.size, (255, 255, 255))).convert('L').point(lambda v: 255 if v > 18 else 0)
    box = diff.getbbox()
    if box:
        pad = int(max(box[2] - box[0], box[3] - box[1]) * 0.04)
        im = im.crop((max(0, box[0] - pad), max(0, box[1] - pad), min(im.width, box[2] + pad), min(im.height, box[3] + pad)))
    im.thumbnail((800, 800))
    return im


def slug(s):
    tr = dict(zip('абвгдеёжзийклмнопрстуфхцчшщъыьэюя',
                  ['a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't',
                   'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya']))
    return re.sub(r'[^a-z0-9]+', '-', ''.join(tr.get(c, c) for c in s.lower())).strip('-')


def main(paths):
    by_sku = {}
    for p in paths:
        by_sku.update(read_photos(p))
    catalog = json.load(open(CATALOG, encoding='utf-8'))
    os.makedirs(OUT_DIR, exist_ok=True)
    lines = sizes = 0
    no_line, no_size = [], []
    for g in catalog:
        for it in g['items']:
            it.pop('image', None)
            for v in it['variants']:
                v.pop('image', None)
                if v['sku'] not in by_sku:
                    no_size.append(v['sku'])
                    continue
                name = slug(v['sku']) or 'sku'
                clean(by_sku[v['sku']]).save(os.path.join(OUT_DIR, name + '.webp'), 'WEBP', quality=82)
                v['image'] = '/assets/img/catalog/%s.webp' % name
                sizes += 1
                it.setdefault('image', v['image'])
            if 'image' in it:
                lines += 1
            else:
                no_line.append(it['name'])
    with open(CATALOG, 'w', encoding='utf-8') as f:
        f.write(json.dumps(catalog, ensure_ascii=False, indent=4) + '\n')
    print('sizes with photo: %d, product lines with photo: %d' % (sizes, lines))
    if no_line:
        print('lines without photo: ' + ', '.join(no_line))
    if no_size:
        print('sizes without own photo: ' + ', '.join(no_size))


if __name__ == '__main__':
    main(sys.argv[1:])
