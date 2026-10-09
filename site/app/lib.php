<?php
// Core helpers: config, rendering, article storage, HTML sanitizing, admin auth.

const STORAGE = __DIR__ . '/../storage';
const UPLOADS = __DIR__ . '/../uploads';
// Every storage file starts with this guard so it can never be served as data.
const GUARD = "<?php http_response_code(404); exit; ?>\n";

function cfg($key = null)
{
    static $c;
    if ($c === null) $c = require __DIR__ . '/config.php';
    return $key === null ? $c : ($c[$key] ?? null);
}

function data($key)
{
    static $d;
    if ($d === null) $d = require __DIR__ . '/data.php';
    return $d[$key];
}

function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function img($name)
{
    return '/assets/img/' . $name . '.webp';
}

function asset($path)
{
    $file = __DIR__ . '/../assets/' . $path;
    return '/assets/' . $path . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function abs_url($path)
{
    return rtrim(cfg('base_url'), '/') . $path;
}

function wa_link($text = 'Здравствуйте! Хочу узнать стоимость обработки.')
{
    return 'https://wa.me/' . cfg('whatsapp') . '?text=' . rawurlencode($text);
}

function ru_date($date)
{
    $m = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
    $t = strtotime($date);
    return date('j', $t) . ' ' . $m[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

function price_label($price)
{
    return $price === null ? 'по запросу' : 'от ' . number_format($price, 0, '', "\u{00A0}") . "\u{00A0}₸";
}

function money($n)
{
    return number_format((int)$n, 0, '', "\u{00A0}") . "\u{00A0}₸";
}

// ---------------------------------------------------------------- disinfectants catalog

/** Price list: the admin's version from storage, else the bundled app/catalog.json. */
function catalog()
{
    static $c;
    if ($c === null) {
        $c = storage_read('catalog.php');
        if (!is_array($c)) $c = json_decode((string)@file_get_contents(__DIR__ . '/catalog.json'), true) ?: [];
    }
    return $c;
}

/** Save the catalog edited in the admin; the previous version is kept for one-step rollback. */
function catalog_save(array $cat)
{
    storage_write('catalog-prev.php', catalog());
    storage_write('catalog.php', array_values($cat));
}

function catalog_count(array $cat)
{
    $n = 0;
    foreach ($cat as $g) foreach ($g['items'] as $it) $n += count($it['variants']);
    return $n;
}

/**
 * Parse the supplier price list exported from Google Sheets as CSV
 * (columns: артикул, -, наименование, фото, состав, срок годности, ед. изм, цена с НДС).
 * Rows with only text are category headings; sizes of one line are grouped into one card.
 */
function catalog_from_csv($file)
{
    $fix = ['Крем для рук' => 'Диспенсеры'];  // a heading in the sheet that does not match its rows
    $short = [
        'Антисептики спиртовые для рук, кожи и поверхностей' => 'Спиртовые антисептики',
        'Антисептики бесспиртовые для рук, кожи и поверхностей' => 'Бесспиртовые антисептики',
        'Влажные салфетки, дезинфицирующие' => 'Салфетки',
        'Универсальные дезинфицирующие средства (концентраты, с моющим действием)' => 'Концентраты',
        'Препараты для стерилизации и ДВУ' => 'Стерилизация и ДВУ',
        'Хлорсодержащие средства в таблетках и гранулах' => 'Хлорные таблетки',
        'Средство для предстерилизационной очистки' => 'ПСО',
        'Дезинфицирующее мыло' => 'Мыло',
    ];
    $h = fopen($file, 'r');
    if (!$h) return [];
    $bom = fread($h, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($h);
    $cats = [];
    $ci = -1;
    $first = true;
    while (($r = fgetcsv($h, 0, ',', '"', '')) !== false) {
        if ($first) { $first = false; continue; }  // header row
        $r = array_map(function ($v) { return str_replace("\u{00A0}", ' ', (string)$v); }, array_pad($r, 8, ''));
        $art = trim($r[0]);
        $name = trim($r[2]);
        if (($art !== '' && $name === '') || ($name !== '' && $art === '' && trim($r[7]) === '')) {
            $title = trim(preg_replace('/^[\d.]+\s*/u', '', $art !== '' ? $art : $name));
            $title = $fix[$title] ?? $title;
            $cats[] = ['slug' => trim(substr(slugify($title), 0, 40), '-'), 'title' => $title, 'short' => $short[$title] ?? $title, 'items' => []];
            $ci = count($cats) - 1;
            continue;
        }
        if ($art === '' || $name === '' || $ci < 0) continue;
        $toOrder = (bool)preg_match('/под заказ/iu', $name);
        $name = trim(preg_replace('/\s*под заказ\s*/iu', ' ', $name));
        $name = preg_replace('/\s+/u', ' ', $name);
        [$base, $size] = catalog_split_name($name);
        $ii = null;
        foreach ($cats[$ci]['items'] as $k => $it) if ($it['name'] === $base) $ii = $k;
        if ($ii === null) {
            $equipment = $cats[$ci]['title'] === 'Диспенсеры';  // containers: no composition or shelf life
            $cats[$ci]['items'][] = ['slug' => slugify($base), 'name' => $base,
                'composition' => $equipment ? [] : catalog_lines($r[4]), 'shelf' => $equipment ? '' : catalog_shelf($r[5]), 'variants' => []];
            $ii = count($cats[$ci]['items']) - 1;
        }
        $cats[$ci]['items'][$ii]['variants'][] = ['sku' => $art, 'size' => $size, 'unit' => trim($r[6]) ?: 'шт',
            'price' => (int)preg_replace('/\D/', '', $r[7]), 'to_order' => $toOrder, 'title' => $name];
    }
    fclose($h);
    return array_values(array_filter($cats, function ($g) { return $g['items']; }));
}

function catalog_lines($text)
{
    $lines = [];
    foreach (explode("\n", str_replace("\r", '', $text)) as $ln) {
        $ln = trim(preg_replace('/\s+/u', ' ', $ln), ' ,.');
        if ($ln === '') continue;
        // a wrapped line of one ingredient: «… хлорид и» + «дидецил… хлорид (суммарно) 3%»
        if ($lines && (substr($lines[count($lines) - 1], -3) === ' и' || $ln[0] === '(' || strpos($ln, 'и ') === 0)) {
            $lines[count($lines) - 1] .= ' ' . $ln;
        } else {
            $lines[] = $ln;
        }
    }
    return $lines;
}

function catalog_shelf($text)
{
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if ($text === '') return '';
    if (strpos($text, '/') === false) return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    [$pack, $sol] = array_map('trim', explode('/', $text, 2));
    return 'Срок годности ' . $pack . ($sol !== '' && $sol !== '-' ? ', рабочего раствора ' . $sol : '');
}

/** «Алмадез-Ликвид, 1л. (антисептик, крышка)» → ['Алмадез-Ликвид', '1 л · крышка']. */
function catalog_split_name($name)
{
    $lowerFirst = function ($b) {
        $f = mb_substr($b, 0, 1);
        $upper = preg_match('/\p{L}/u', $b) && !preg_match('/\p{Ll}/u', $b);
        return ($upper || ($f !== mb_strtolower($f))) ? mb_strtolower($b) : $b;
    };
    if (strpos($name, 'Салфетки') === 0 && preg_match('/^(.*?)\s+(№\s*\d+|САШЕ)\s*(.*)$/u', $name, $m)) {
        // «Салфетки влажные Алмадез-Ликвид №200 (12*20) (В ВЕДРЕ)», «… САШЕ (8*8) (100шт. уп.)»
        $head = str_replace('САШЕ', 'саше', preg_replace('/№\s*(\d+)/u', '№ $1 шт.', $m[2]));
        preg_match_all('/\(([^)]*)\)/u', $m[3], $bm);
        $bits = [];
        foreach ($bm[1] as $b) {
            $b = $lowerFirst(trim($b, ' .'));
            $bits[] = preg_replace('/(\d+)шт\. уп/u', '$1 шт. в упаковке', $b);
        }
        return [trim($m[1], ' ,'), implode(' · ', array_merge([$head], $bits))];
    }
    if (preg_match('/^(.*?),\s*(.+)$/u', $name, $m) || preg_match('/^(.*?)\s+(№\s*\d.*)$/u', $name, $m)) {
        [$base, $rest] = [$m[1], $m[2]];
    } else {
        [$base, $rest] = [$name, ''];
    }
    $rest = str_replace(['(', ')'], ' ', $rest);
    $parts = array_values(array_filter(array_map('trim', preg_split('/[,\s]{2,}|,/u', $rest)), function ($p) {
        return $p !== '' && !in_array(mb_strtolower($p), ['антисептик', 'мыло', 'концентрат'], true);
    }));
    $size = $parts[0] ?? '';
    $size = preg_replace('/(\d)\s*(мл|л|кг|г)\.?$/u', '$1 $2', $size);
    $size = preg_replace('/№\s*(\d+)/u', '№ $1', $size);
    $more = array_map(function ($p) {
        return (mb_strlen($p) > 3 && preg_match('/\p{L}/u', $p) && !preg_match('/\p{Ll}/u', $p)) ? mb_strtolower($p) : $p;
    }, array_slice($parts, 1));
    return [trim($base), trim(implode(' · ', array_merge([$size], $more)), ' ·')];
}

function schema_catalog()
{
    $list = [];
    foreach (catalog() as $g) {
        foreach ($g['items'] as $it) {
            $prices = array_column($it['variants'], 'price');
            $list[] = [
                '@type' => 'ListItem', 'position' => count($list) + 1,
                'item' => [
                    '@type' => 'Product', 'name' => $it['name'], 'category' => $g['title'],
                    'url' => abs_url('/dezsredstva#' . $it['slug']),
                    'description' => trim($g['title'] . '. ' . $it['shelf']),
                    'offers' => [
                        '@type' => 'AggregateOffer', 'priceCurrency' => 'KZT',
                        'lowPrice' => min($prices), 'highPrice' => max($prices), 'offerCount' => count($prices),
                        'seller' => ['@id' => cfg('base_url') . '/#business'],
                    ],
                ],
            ];
        }
    }
    return ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Дезинфицирующие средства', 'itemListElement' => $list];
}

function view($template, array $vars = [])
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require __DIR__ . '/views/' . $template . '.php';
    return ob_get_clean();
}

/** Render a public page inside the main layout. $page keys: title, description, canonical, schema[], body. */
function render_page(array $page)
{
    $html = view('layout', ['page' => $page]);
    echo lang() === 'kk' ? to_kazakh($html) : $html;
}

// ---------------------------------------------------------------- Kazakh version

/** Pages that exist in Kazakh at /kk/... (everything else stays Russian). */
const KK_PAGES = '#^/(|uslugi|uslugi/[a-z0-9-]+|ceny|kontakty)$#';

function lang($set = null)
{
    static $lang = 'ru';
    if ($set !== null) $lang = $set;
    return $lang;
}

function has_kk($path)
{
    return (bool)preg_match(KK_PAGES, rtrim($path, '/') === '' ? '/' : rtrim($path, '/'));
}

/** Translate rendered HTML with the phrase dictionary and point links to the Kazakh pages. */
function to_kazakh($html)
{
    static $dict;
    if ($dict === null) {
        $dict = require __DIR__ . '/i18n/kk.php';
        // prices are printed with non-breaking spaces: add those spellings of every "от N ₸" entry
        foreach ($dict as $ru => $kk) {
            if (preg_match('/^от [0-9 ]+ ₸$/u', $ru)) $dict[str_replace(' ', "\u{00A0}", $ru)] = str_replace(' ', "\u{00A0}", $kk);
        }
        uksort($dict, function ($a, $b) { return mb_strlen($b) - mb_strlen($a); });
    }
    $html = str_replace('<html lang="ru">', '<html lang="kk">', $html);
    $html = str_replace('content="ru_RU"', 'content="kk_KZ"', $html);
    $html = strtr($html, $dict);
    // links to pages that have a Kazakh version go to /kk; the language switch (hreflang="ru") stays Russian
    return preg_replace_callback('#<a\b[^>]*>#', function ($tag) {
        if (strpos($tag[0], 'hreflang="ru"') !== false) return $tag[0];
        return preg_replace_callback('#href="(/[^"\#?]*)([\#?][^"]*)?"#', function ($m) {
            $path = $m[1] === '/' ? '/' : rtrim($m[1], '/');
            if (strpos($path, '/kk') === 0 || !has_kk($path)) return $m[0];
            return 'href="/kk' . ($path === '/' ? '' : $path) . ($m[2] ?? '') . '"';
        }, $tag[0]);
    }, $html);
}

function not_found()
{
    http_response_code(404);
    render_page([
        'title' => 'Страница не найдена',
        'description' => '',
        'canonical' => null,
        'noindex' => true,
        'body' => view('pages/404'),
    ]);
    exit;
}

function redirect($to, $code = 302)
{
    header('Location: ' . $to, true, $code);
    exit;
}

// ---------------------------------------------------------------- storage

function storage_read($file)
{
    $path = STORAGE . '/' . $file;
    if (!is_file($path)) return null;
    $raw = file_get_contents($path);
    if (strpos($raw, GUARD) === 0) $raw = substr($raw, strlen(GUARD));
    return json_decode($raw, true);
}

function storage_write($file, $value)
{
    $path = STORAGE . '/' . $file;
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0750, true);
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    file_put_contents($tmp, GUARD . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    rename($tmp, $path);
}

// ---------------------------------------------------------------- articles

function article_file($id)
{
    return 'articles/' . preg_replace('/[^a-z0-9]/', '', $id) . '.php';
}

/** Articles: bundled ones from app/articles (shipped with the site) overridden by admin-edited copies in storage. */
function articles_all($onlyPublished = true)
{
    $byId = [];
    foreach (glob(__DIR__ . '/articles/*.json') ?: [] as $path) {
        $a = json_decode(file_get_contents($path), true);
        if ($a) $byId[$a['id']] = $a + ['bundled' => true];
    }
    foreach (glob(STORAGE . '/articles/*.php') ?: [] as $path) {
        $a = storage_read('articles/' . basename($path));
        if ($a) $byId[$a['id']] = $a;
    }
    $list = [];
    foreach ($byId as $a) {
        if (($a['status'] ?? '') === 'deleted') continue;
        if ($onlyPublished && !article_is_live($a)) continue;
        $list[] = $a;
    }
    usort($list, function ($x, $y) {
        return strcmp($y['published_at'] ?: $y['updated_at'], $x['published_at'] ?: $x['updated_at']);
    });
    return $list;
}

function article_is_live(array $a)
{
    // static preview build shows scheduled articles too
    if (getenv('SHOW_SCHEDULED') === '1' && $a['status'] === 'published') return true;
    return $a['status'] === 'published' && $a['published_at'] <= date('Y-m-d H:i');
}

function article_by_slug($slug)
{
    foreach (articles_all(true) as $a) {
        if ($a['slug'] === $slug) return $a;
    }
    return null;
}

function article_get($id)
{
    foreach (articles_all(false) as $a) {
        if ($a['id'] === $id) return $a;
    }
    return null;
}

function article_save(array $a)
{
    unset($a['bundled']);
    if (empty($a['id'])) $a['id'] = bin2hex(random_bytes(6));
    $a['updated_at'] = date('Y-m-d H:i');
    storage_write(article_file($a['id']), $a);
    return $a;
}

function article_delete($id)
{
    $a = article_get($id);
    if (!$a) return;
    if (!empty($a['bundled'])) {
        // bundled articles live in the code, so deletion is remembered as a marker in storage
        storage_write(article_file($id), ['id' => $id, 'status' => 'deleted', 'published_at' => '', 'updated_at' => date('Y-m-d H:i')]);
        return;
    }
    $path = STORAGE . '/' . article_file($id);
    if (is_file($path)) unlink($path);
}

function slugify($s)
{
    $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
        'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
        'х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        'ә'=>'a','ғ'=>'g','қ'=>'k','ң'=>'n','ө'=>'o','ұ'=>'u','ү'=>'u','һ'=>'h','і'=>'i'];
    $s = strtr(mb_strtolower(trim($s), 'UTF-8'), $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(substr($s, 0, 80), '-');
}

function slug_taken($slug, $exceptId)
{
    foreach (articles_all(false) as $a) {
        if ($a['slug'] === $slug && $a['id'] !== $exceptId) return true;
    }
    return false;
}

/** "Question\nAnswer" blocks separated by an empty line → [[q, a], ...] */
function parse_faq($text)
{
    $out = [];
    foreach (preg_split('/\R\s*\R/u', trim((string)$text)) as $block) {
        $lines = preg_split('/\R/u', trim($block));
        $q = trim(array_shift($lines));
        $a = trim(implode(' ', array_map('trim', $lines)));
        if ($q !== '' && $a !== '') $out[] = ['q' => $q, 'a' => $a];
    }
    return $out;
}

function faq_to_text(array $faq)
{
    return implode("\n\n", array_map(function ($f) { return $f['q'] . "\n" . $f['a']; }, $faq));
}

function parse_lines($text)
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)$text)), 'strlen'));
}

/** Author node for Article schema: the configured expert, otherwise the company. */
function schema_author()
{
    $x = cfg('expert');
    if (!empty($x['name'])) {
        return array_filter(['@type' => 'Person', 'name' => $x['name'], 'jobTitle' => $x['role'],
            'worksFor' => ['@id' => cfg('base_url') . '/#business']]);
    }
    return ['@type' => 'Organization', 'name' => cfg('legal_name'), '@id' => cfg('base_url') . '/#business'];
}

function reading_minutes($html)
{
    $words = count(preg_split('/\s+/u', trim(strip_tags($html))));
    return max(1, (int)round($words / 180));
}

function excerpt($a, $len = 180)
{
    $t = $a['excerpt'] ?: $a['meta_description'] ?: trim(preg_replace('/\s+/u', ' ', strip_tags($a['body'])));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len - 1)) . '…' : $t;
}

// ---------------------------------------------------------------- sanitizer

/** Keep only safe tags/attributes from the editor's HTML. */
function sanitize_html($html)
{
    $allowed = [
        'p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
        'u' => [], 's' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [],
        'a' => ['href', 'target', 'rel'], 'img' => ['src', 'alt', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
    ];
    $html = trim((string)$html);
    if ($html === '') return '';
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('root');
    if (!$root) return '';
    sanitize_node($root, $allowed);
    $out = '';
    foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
    // Quill marks list type with data-list; it is stripped above, so ordered lists are kept by tag name
    return preg_replace('#<p><br></p>#', '', $out);
}

function sanitize_node(DOMNode $node, array $allowed)
{
    for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
        $child = $node->childNodes->item($i);
        if ($child instanceof DOMElement) {
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'svg', 'math'], true)) {
                $node->removeChild($child);
                continue;
            }
            sanitize_node($child, $allowed);
            if (!isset($allowed[$tag])) {
                // unwrap: keep text/children, drop the tag itself
                while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                if (!in_array($attr->name, $allowed[$tag], true)) $child->removeAttribute($attr->name);
            }
            foreach (['href', 'src'] as $urlAttr) {
                if ($child->hasAttribute($urlAttr) && !safe_url($child->getAttribute($urlAttr))) {
                    $child->removeAttribute($urlAttr);
                }
            }
            if ($tag === 'a' && preg_match('#^https?://#i', $child->getAttribute('href'))
                && strpos($child->getAttribute('href'), cfg('base_url')) !== 0) {
                $child->setAttribute('target', '_blank');
                $child->setAttribute('rel', 'noopener');
            }
            if ($tag === 'img') {
                if (!$child->hasAttribute('src')) { $node->removeChild($child); continue; }
                $child->setAttribute('loading', 'lazy');
            }
        } elseif ($child->nodeType === XML_COMMENT_NODE) {
            $node->removeChild($child);
        }
    }
}

function safe_url($url)
{
    $url = trim($url);
    return $url !== '' && (preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $url) === 1) && strpos($url, '//') !== 0;
}

// ---------------------------------------------------------------- admin auth

function session_start_secure()
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('dzadmin');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/admin', 'httponly' => true, 'samesite' => 'Strict',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_check()
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Сессия устарела. Обновите страницу и попробуйте ещё раз.');
    }
}

function admin_user()
{
    return storage_read('admin.php');
}

function admin_logged_in()
{
    return !empty($_SESSION['admin']) && ($_SESSION['admin_hash'] ?? '') === (admin_user()['hash'] ?? '-');
}

/** First-run setup key, stored only on the server (owner reads it in the file manager). */
function setup_key()
{
    $k = storage_read('setup-key.php');
    if (!$k) {
        $k = ['key' => bin2hex(random_bytes(8))];
        storage_write('setup-key.php', $k);
    }
    return $k['key'];
}

function login_throttled()
{
    $ip = preg_replace('/[^0-9a-f.:]/i', '', $_SERVER['REMOTE_ADDR'] ?? 'x');
    $log = storage_read('login-attempts.php') ?: [];
    $now = time();
    $recent = array_filter($log[$ip] ?? [], function ($t) use ($now) { return $t > $now - 900; });
    return count($recent) >= 5;
}

function login_failed()
{
    $ip = preg_replace('/[^0-9a-f.:]/i', '', $_SERVER['REMOTE_ADDR'] ?? 'x');
    $log = storage_read('login-attempts.php') ?: [];
    $now = time();
    foreach ($log as $k => $times) {
        $log[$k] = array_values(array_filter($times, function ($t) use ($now) { return $t > $now - 900; }));
        if (!$log[$k]) unset($log[$k]);
    }
    $log[$ip][] = $now;
    storage_write('login-attempts.php', $log);
}

// ---------------------------------------------------------------- schema.org

function schema_business()
{
    $c = cfg();
    return [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        '@id' => $c['base_url'] . '/#business',
        'name' => $c['name'],
        'url' => $c['base_url'] . '/',
        'telephone' => $c['phone'],
        'image' => abs_url(img('logo')),
        'logo' => abs_url(img('logo')),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $c['address'],
            'addressLocality' => $c['city'],
            'addressCountry' => 'KZ',
        ],
        'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $c['geo']['lat'], 'longitude' => $c['geo']['lng']],
        'openingHours' => $c['hours_schema'],
        'areaServed' => $c['city'],
        'sameAs' => array_values(array_filter([$c['gis_firm'], $c['instagram']])),
    ];
}

function schema_faq(array $faq)
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(function ($f) {
            return ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]];
        }, $faq),
    ];
}

function schema_breadcrumbs(array $items)
{
    $list = [];
    $i = 1;
    foreach ($items as $name => $path) {
        $list[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $name, 'item' => abs_url($path)];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}
