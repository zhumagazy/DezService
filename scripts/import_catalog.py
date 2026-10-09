"""Turn the supplier price list (Google Sheet exported as CSV) into site/app/catalog.json.

Usage: python3 scripts/import_catalog.py path/to/price.csv
Export: Google Sheets → Файл → Скачать → CSV (the «АЛМАДЕЗ» sheet).

Rows with an article number are products; rows with only text in the first column
are category headings («1.1. Антисептики …»). Sizes of one product line are grouped
into one card: «Алмадез-Ликвид, 1л. (крышка)» and «…, 5л. (евро)» → «Алмадез-Ликвид».
"""
import csv
import json
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, 'site', 'app', 'catalog.json')

# headings in the sheet that do not match what is listed under them
CATEGORY_FIX = {'Крем для рук': 'Диспенсеры'}
# short labels for the category bar
SHORT = {
    'Антисептики спиртовые для рук, кожи и поверхностей': 'Спиртовые антисептики',
    'Антисептики бесспиртовые для рук, кожи и поверхностей': 'Бесспиртовые антисептики',
    'Влажные салфетки, дезинфицирующие': 'Салфетки',
    'Универсальные дезинфицирующие средства (концентраты, с моющим действием)': 'Концентраты',
    'Препараты для стерилизации и ДВУ': 'Стерилизация и ДВУ',
    'Хлорсодержащие средства в таблетках и гранулах': 'Хлорные таблетки',
    'Средство для предстерилизационной очистки': 'ПСО',
    'Дезинфицирующее мыло': 'Мыло',
}
# words repeated in every size of a line, they add nothing next to the line name
NOISE = {'антисептик', 'мыло', 'концентрат'}


def slugify(s):
    tr = dict(zip('абвгдеёжзийклмнопрстуфхцчшщъыьэюя',
                  ['a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't',
                   'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya']))
    s = ''.join(tr.get(c, c) for c in s.lower())
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')


def clean_lines(text):
    lines = []
    for ln in text.replace('\xa0', ' ').split('\n'):
        ln = re.sub(r'\s+', ' ', ln).strip(' ,.')
        if not ln:
            continue
        # a wrapped line of one ingredient: «… хлорид и» + «дидецил… хлорид (суммарно) 3%»
        if lines and (lines[-1].endswith(' и') or ln[:1] == '(' or ln.startswith('и ')):
            lines[-1] += ' ' + ln
        else:
            lines.append(ln)
    return lines


def split_name(name):
    """«Алмадез-Ликвид, 1л. (антисептик, крышка)» → («Алмадез-Ликвид», «1 л · крышка»)."""
    if name.startswith('Салфетки'):
        # «Салфетки влажные Алмадез-Ликвид №200 (12*20) (В ВЕДРЕ)», «… САШЕ (8*8) (100шт. уп.)»
        m = re.match(r'^(.*?)\s+(№\s*\d+|САШЕ)\s*(.*)$', name)
        head = re.sub(r'№\s*(\d+)', r'№ \1 шт.', m.group(2)).replace('САШЕ', 'саше')
        bits = [b.strip(' .') for b in re.findall(r'\(([^)]*)\)', m.group(3))]
        bits = [b.lower() if b.isupper() or b[:1].isupper() else b for b in bits]
        bits = [re.sub(r'(\d+)шт\. уп', r'\1 шт. в упаковке', b) for b in bits]
        return m.group(1).strip(' ,'), ' · '.join([head] + bits)
    m = re.match(r'^(.*?),\s*(.+)$', name)
    if not m:
        m = re.match(r'^(.*?)\s+(№\s*\d.*)$', name)
    base, rest = (m.group(1), m.group(2)) if m else (name, '')
    rest = rest.replace('(', ' ').replace(')', ' ')
    parts = [p.strip() for p in re.split(r'[,\s]{2,}|,', rest) if p.strip()]
    parts = [p for p in parts if p.lower() not in NOISE]
    size = parts[0] if parts else ''
    size = re.sub(r'(\d)\s*(мл|л|кг|г)\.?$', r'\1 \2', size)
    size = re.sub(r'№\s*(\d+)', r'№ \1', size)
    variant = ' · '.join([size] + [p.lower() if p.isupper() and len(p) > 3 else p for p in parts[1:]]).strip(' ·')
    return base.strip(), variant


def shelf(text):
    text = re.sub(r'\s+', ' ', text.replace('\xa0', ' ')).strip()
    if not text:
        return ''
    if '/' not in text:
        return text[0].upper() + text[1:]
    pack, sol = [x.strip() for x in text.split('/', 1)]
    out = 'Срок годности ' + pack
    if sol and sol != '-':
        out += ', рабочего раствора ' + sol
    return out


def main(path):
    rows = list(csv.reader(open(path, encoding='utf-8')))
    categories, cat = [], None
    for r in rows[1:]:
        r += [''] * (8 - len(r))
        art, name = r[0].strip(), r[2].strip()
        if (art and not name) or (name and not art and not r[7].strip()):  # category heading, in column A or C
            title = re.sub(r'^[\d.]+\s*', '', art or name).strip()
            title = CATEGORY_FIX.get(title, title)
            cat = {'slug': slugify(title)[:40].strip('-'), 'title': title, 'short': SHORT.get(title, title), 'items': []}
            categories.append(cat)
            continue
        if not art or not name or cat is None:
            continue
        price = int(re.sub(r'\D', '', r[7]) or 0)
        order = bool(re.search(r'под заказ', name, re.I))
        name = re.sub(r'\s*под заказ\s*', ' ', name, flags=re.I).strip()
        base, variant = split_name(re.sub(r'\s+', ' ', name))
        item = next((i for i in cat['items'] if i['name'] == base), None)
        if item is None:
            equipment = cat['title'] == 'Диспенсеры'  # containers: no composition or shelf life
            item = {'slug': slugify(base), 'name': base, 'composition': [] if equipment else clean_lines(r[4]),
                    'shelf': '' if equipment else shelf(r[5]), 'variants': []}
            cat['items'].append(item)
        item['variants'].append({'sku': art, 'size': variant, 'unit': r[6].strip() or 'шт',
                                 'price': price, 'to_order': order, 'title': name})
    with open(OUT, 'w', encoding='utf-8') as f:
        json.dump(categories, f, ensure_ascii=False, indent=1)
    n = sum(len(v['variants']) for c in categories for v in c['items'])
    print('categories %d, product lines %d, positions %d → %s' % (
        len(categories), sum(len(c['items']) for c in categories), n, OUT))


if __name__ == '__main__':
    main(sys.argv[1])
