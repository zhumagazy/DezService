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

function price_label($price)
{
    return $price === null ? 'по запросу' : 'от ' . number_format($price, 0, '', ' ') . ' ₸';
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
    echo view('layout', ['page' => $page]);
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

function articles_all($onlyPublished = true)
{
    $list = [];
    foreach (glob(STORAGE . '/articles/*.php') ?: [] as $path) {
        $a = storage_read('articles/' . basename($path));
        if (!$a) continue;
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
    return storage_read(article_file($id));
}

function article_save(array $a)
{
    if (empty($a['id'])) $a['id'] = bin2hex(random_bytes(6));
    $a['updated_at'] = date('Y-m-d H:i');
    storage_write(article_file($a['id']), $a);
    return $a;
}

function article_delete($id)
{
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
