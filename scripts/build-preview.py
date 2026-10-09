"""Build a static preview of site/ for GitHub Pages: preview/ served at /DezService/preview/.

Runs the PHP site with the built-in server, saves every public page as HTML and
rewrites root-relative links to the Pages sub-path. The admin panel and forms that
need PHP are not part of the preview. Usage: python3 scripts/build-preview.py
"""
import os
import re
import shutil
import subprocess
import time
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
SITE = os.path.join(ROOT, 'site')
OUT = os.path.join(ROOT, 'preview')
PREFIX = '/DezService/preview'
PORT = 8790
PAGES = ['/', '/uslugi', '/ceny', '/otzyvy', '/o-kompanii', '/kontakty',
         '/politika-konfidencialnosti', '/blog', '/kk', '/kk/uslugi', '/kk/ceny', '/kk/kontakty']


def fetch(path):
    with urllib.request.urlopen('http://127.0.0.1:%d%s' % (PORT, path)) as r:
        return r.read().decode('utf-8')


def rewrite(html):
    def fix(m):
        attr, url = m.group(1), m.group(2)
        if url.startswith('//') or url.startswith('/admin'):
            return m.group(0)
        path, _, rest = url.partition('?')
        if path.startswith('/assets/') or path.startswith('/uploads/') or '.' in os.path.basename(path):
            return '%s="%s%s"' % (attr, PREFIX, url)  # files keep their name (query string is harmless)
        path, hash_, frag = path.partition('#')
        path = path.rstrip('/') + '/'
        return '%s="%s%s%s%s"' % (attr, PREFIX, path, hash_, frag)
    html = re.sub(r'(href|src)="(/[^"]*)"', fix, html)
    # preview copy must not compete with the real site in search engines
    return html.replace('<meta charset="utf-8">', '<meta charset="utf-8">\n<meta name="robots" content="noindex, nofollow">', 1)


def main():
    server = subprocess.Popen(['php', '-S', '127.0.0.1:%d' % PORT, '-t', SITE, os.path.join(SITE, 'dev-router.php')],
                              stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
                              env=dict(os.environ, SHOW_SCHEDULED='1'))
    try:
        time.sleep(1)
        shutil.rmtree(OUT, ignore_errors=True)
        pages = list(PAGES)
        services = re.findall(r'href="(/uslugi/[a-z0-9-]+)"', fetch('/uslugi'))
        blog_pages = sorted(set(re.findall(r'href="(/blog/page/[0-9]+)"', fetch('/blog'))))
        articles = []
        for bp in ['/blog'] + blog_pages:
            articles += re.findall(r'href="(/blog/(?!page/)[a-z0-9-]+)"', fetch(bp))
        pages += blog_pages
        services += ['/kk' + x for x in services]
        for p in sorted(set(services + articles)):
            pages.append(p)
        for p in pages:
            target = os.path.join(OUT, p.strip('/'), 'index.html')
            os.makedirs(os.path.dirname(target), exist_ok=True)
            with open(target, 'w', encoding='utf-8') as f:
                f.write(rewrite(fetch(p)))
            print('page', p)
        shutil.copytree(os.path.join(SITE, 'assets'), os.path.join(OUT, 'assets'))
        shutil.copy(os.path.join(SITE, 'favicon.ico'), OUT)
        uploads = os.path.join(SITE, 'uploads')
        if any(n for n in os.listdir(uploads) if not n.startswith('.')):
            shutil.copytree(uploads, os.path.join(OUT, 'uploads'), ignore=shutil.ignore_patterns('.*'))
    finally:
        server.terminate()


if __name__ == '__main__':
    main()
