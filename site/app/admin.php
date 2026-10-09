<?php
// Admin panel for articles: /admin (list), /admin/edit, /admin/save, /admin/delete, /admin/upload, login/logout/setup.
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
session_start_secure();

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';

function admin_page($template, array $vars = [])
{
    echo view('admin/layout', ['body' => view('admin/' . $template, $vars), 'title' => $vars['title'] ?? 'Админ-панель']);
    exit;
}

// ---------- first run: create the admin password with the setup key from storage/setup-key.php
if (!admin_user()) {
    $error = '';
    if ($post && $path === '/admin/setup') {
        csrf_check();
        $pass = (string)($_POST['password'] ?? '');
        if (login_throttled()) {
            $error = 'Слишком много попыток. Подождите 15 минут.';
        } elseif (!hash_equals(setup_key(), trim((string)($_POST['key'] ?? '')))) {
            login_failed();
            $error = 'Неверный ключ установки.';
        } elseif (mb_strlen($pass) < 10) {
            $error = 'Пароль должен быть не короче 10 символов.';
        } else {
            storage_write('admin.php', ['login' => trim($_POST['login'] ?? 'admin') ?: 'admin', 'hash' => password_hash($pass, PASSWORD_DEFAULT)]);
            @unlink(STORAGE . '/setup-key.php');
            redirect('/admin/login');
        }
    }
    setup_key();
    admin_page('setup', ['title' => 'Первый вход', 'error' => $error]);
}

// ---------- login / logout
if ($path === '/admin/login') {
    $error = '';
    if ($post) {
        csrf_check();
        $user = admin_user();
        if (login_throttled()) {
            $error = 'Слишком много попыток. Подождите 15 минут.';
        } elseif (hash_equals($user['login'], (string)($_POST['login'] ?? '')) && password_verify((string)($_POST['password'] ?? ''), $user['hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['admin_hash'] = $user['hash'];
            redirect('/admin');
        } else {
            login_failed();
            $error = 'Неверный логин или пароль.';
        }
    }
    admin_page('login', ['title' => 'Вход', 'error' => $error]);
}

if (!admin_logged_in()) redirect('/admin/login');

if ($path === '/admin/logout' && $post) {
    csrf_check();
    $_SESSION = [];
    session_destroy();
    redirect('/admin/login');
}

// ---------- password change
if ($path === '/admin/password') {
    $error = $ok = '';
    if ($post) {
        csrf_check();
        $user = admin_user();
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), $user['hash'])) {
            $error = 'Текущий пароль указан неверно.';
        } elseif (mb_strlen($new) < 10) {
            $error = 'Новый пароль должен быть не короче 10 символов.';
        } else {
            $user['hash'] = password_hash($new, PASSWORD_DEFAULT);
            storage_write('admin.php', $user);
            $_SESSION['admin_hash'] = $user['hash'];
            $ok = 'Пароль изменён.';
        }
    }
    admin_page('password', ['title' => 'Смена пароля', 'error' => $error, 'ok' => $ok]);
}

// ---------- image upload (used by the editor and the cover field), returns JSON
if ($path === '/admin/upload' && $post) {
    csrf_check();
    header('Content-Type: application/json');
    $f = $_FILES['file'] ?? null;
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 8 * 1024 * 1024) {
        http_response_code(400);
        exit(json_encode(['error' => 'Файл не загружен (максимум 8 МБ).']));
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !isset($types[$info['mime']])) {
        http_response_code(400);
        exit(json_encode(['error' => 'Можно загружать только JPG, PNG или WebP.']));
    }
    $dir = UPLOADS . '/' . date('Y/m');
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(8));
    $url = optimize_upload($f['tmp_name'], $info, $dir . '/' . $name, $types[$info['mime']]);
    exit(json_encode(['url' => '/uploads/' . date('Y/m') . '/' . basename($url)]));
}

// ---------- delete
if ($path === '/admin/delete' && $post) {
    csrf_check();
    article_delete((string)($_POST['id'] ?? ''));
    redirect('/admin?deleted=1');
}

// ---------- edit / save
if ($path === '/admin/edit') {
    $id = (string)($_GET['id'] ?? '');
    $a = $id ? article_get($id) : null;
    if ($id && !$a) redirect('/admin');
    $a = $a ?: ['id' => '', 'title' => '', 'slug' => '', 'h1' => '', 'meta_title' => '', 'meta_description' => '',
                'excerpt' => '', 'summary' => '', 'faq' => [], 'howto' => [], 'cover' => '', 'body' => '', 'status' => 'draft', 'published_at' => date('Y-m-d H:i'), 'updated_at' => ''];
    $errors = [];
    if ($post) {
        csrf_check();
        foreach (['title', 'slug', 'h1', 'meta_title', 'meta_description', 'excerpt', 'cover'] as $k) {
            $a[$k] = trim((string)($_POST[$k] ?? ''));
        }
        $a['body'] = sanitize_html($_POST['body'] ?? '');
        $a['summary'] = trim((string)($_POST['summary'] ?? ''));
        $a['faq'] = parse_faq($_POST['faq'] ?? '');
        $a['howto'] = parse_lines($_POST['howto'] ?? '');
        $a['status'] = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
        $date = str_replace('T', ' ', (string)($_POST['published_at'] ?? ''));
        $a['published_at'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $date) ? $date : date('Y-m-d H:i');
        $a['slug'] = slugify($a['slug'] ?: $a['title']);
        if ($a['cover'] !== '' && !preg_match('#^/(uploads|assets/img/blog)/[\w/.-]+$#', $a['cover'])) $a['cover'] = '';

        if ($a['title'] === '') $errors[] = 'Укажите заголовок.';
        if ($a['slug'] === '') $errors[] = 'Не удалось сформировать адрес страницы — укажите его вручную латиницей.';
        elseif (slug_taken($a['slug'], $a['id'])) $errors[] = 'Статья с таким адресом уже есть.';
        if ($a['status'] === 'published' && trim(strip_tags($a['body'])) === '') $errors[] = 'Нельзя опубликовать пустую статью.';

        if (!$errors) {
            $a = article_save($a);
            redirect('/admin/edit?id=' . $a['id'] . '&saved=1');
        }
    }
    admin_page('edit', ['title' => $a['id'] ? 'Редактирование' : 'Новая статья', 'a' => $a, 'errors' => $errors]);
}

// ---------- disinfectants catalog
if (strpos($path, '/admin/catalog') === 0) {
    $cat = catalog();
    $msg = $err = '';

    if ($path === '/admin/catalog/upload' && $post) {
        csrf_check();
        $f = $_FILES['csv'] ?? null;
        $new = ($f && $f['error'] === UPLOAD_ERR_OK && $f['size'] < 5 * 1024 * 1024) ? catalog_from_csv($f['tmp_name']) : [];
        if (!catalog_count($new)) {
            $err = 'Не удалось прочитать прайс. Скачайте лист из Google-таблицы как CSV (Файл → Скачать → CSV) и загрузите этот файл.';
        } else {
            $old = [];
            foreach ($cat as $g) foreach ($g['items'] as $it) foreach ($it['variants'] as $v) $old[$v['sku']] = $v['price'];
            $added = $changed = 0;
            foreach ($new as $g) foreach ($g['items'] as $it) foreach ($it['variants'] as $v) {
                if (!isset($old[$v['sku']])) $added++;
                elseif ($old[$v['sku']] !== $v['price']) $changed++;
                unset($old[$v['sku']]);
            }
            catalog_save($new);
            $_SESSION['flash'] = sprintf('Прайс загружен. Позиций: %d, категорий: %d. Новых позиций: %d, изменилась цена: %d, убрано: %d.',
                catalog_count($new), count($new), $added, $changed, count($old));
            redirect('/admin/catalog');
        }
    }

    if ($path === '/admin/catalog/rollback' && $post) {
        csrf_check();
        $prev = storage_read('catalog-prev.php');
        if (is_array($prev)) {
            storage_write('catalog.php', $prev);
            @unlink(STORAGE . '/catalog-prev.php');
            $_SESSION['flash'] = 'Вернули предыдущую версию каталога.';
        }
        redirect('/admin/catalog');
    }

    if ($path === '/admin/catalog/category' && $post) {
        csrf_check();
        $title = trim((string)($_POST['title'] ?? ''));
        if ($title !== '') {
            $slug = trim(substr(slugify($title), 0, 40), '-') ?: 'kategoriya';
            while (in_array($slug, array_column($cat, 'slug'), true)) $slug .= '-2';
            $cat[] = ['slug' => $slug, 'title' => $title, 'short' => trim((string)($_POST['short'] ?? '')) ?: $title, 'items' => []];
            catalog_save($cat);
            redirect('/admin/catalog/edit?cat=' . $slug);
        }
        redirect('/admin/catalog');
    }

    if ($path === '/admin/catalog/edit') {
        $ci = array_search((string)($_GET['cat'] ?? ''), array_column($cat, 'slug'), true);
        if ($ci === false) redirect('/admin/catalog');
        $g = $cat[$ci];
        $errors = [];
        if ($post) {
            csrf_check();
            if (!empty($_POST['delete_category'])) {
                array_splice($cat, $ci, 1);
                catalog_save($cat);
                $_SESSION['flash'] = 'Категория «' . $g['title'] . '» удалена.';
                redirect('/admin/catalog');
            }
            $g = catalog_category_from_post($g, $_POST, $errors);
            if (!$errors) {
                $cat[$ci] = $g;
                // product anchors must stay unique on the page
                $seen = [];
                foreach ($cat as &$gg) foreach ($gg['items'] as &$it) {
                    $base = $it['slug'] = slugify($it['name']) ?: 'sredstvo';
                    for ($n = 2; isset($seen[$it['slug']]); $n++) $it['slug'] = $base . '-' . $n;
                    $seen[$it['slug']] = true;
                }
                unset($gg, $it);
                catalog_save($cat);
                redirect('/admin/catalog/edit?cat=' . $g['slug'] . '&saved=1');
            }
        }
        admin_page('catalog-edit', ['title' => $g['title'], 'g' => $g, 'errors' => $errors]);
    }

    $flash = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    admin_page('catalog', ['title' => 'Дезсредства', 'cat' => $cat, 'flash' => $flash, 'err' => $err,
        'has_prev' => is_array(storage_read('catalog-prev.php'))]);
}

/** Rebuild one category from the edit form; rows without article and price are skipped. */
function catalog_category_from_post(array $g, array $in, array &$errors)
{
    $g['title'] = trim((string)($in['title'] ?? '')) ?: $g['title'];
    $g['short'] = trim((string)($in['short'] ?? '')) ?: $g['title'];
    $items = [];
    foreach ((array)($in['items'] ?? []) as $p) {
        if (!empty($p['delete'])) continue;
        $name = trim((string)($p['name'] ?? ''));
        $variants = [];
        foreach ((array)($p['variants'] ?? []) as $v) {
            $sku = trim((string)($v['sku'] ?? ''));
            $size = trim((string)($v['size'] ?? ''));
            $price = trim((string)($v['price'] ?? ''));
            if (!empty($v['delete']) || ($sku === '' && $size === '' && $price === '')) continue;
            if ($price === '' || !preg_match('/^\d[\d\s]*$/u', str_replace("\u{00A0}", ' ', $price))) {
                $errors[] = 'Укажите цену цифрами: ' . ($name ?: 'новое средство') . ($size !== '' ? ', ' . $size : '') . '.';
            }
            $variants[] = ['sku' => $sku, 'size' => $size, 'unit' => trim((string)($v['unit'] ?? '')) ?: 'шт',
                'price' => (int)preg_replace('/\D/', '', $price), 'to_order' => !empty($v['to_order']),
                'title' => $name . ($size !== '' ? ', ' . $size : '')];
        }
        if ($name === '' && !$variants) continue;  // the empty «new product» block
        if ($name === '') $errors[] = 'У средства с фасовками не указано название.';
        if (!$variants) {
            $errors[] = 'У средства «' . $name . '» нет ни одной фасовки с ценой.';
        }
        $items[] = ['slug' => '', 'name' => $name, 'shelf' => trim((string)($p['shelf'] ?? '')),
            'composition' => parse_lines((string)($p['composition'] ?? '')), 'variants' => $variants];
    }
    $g['items'] = $items;
    return $g;
}

if ($path === '/admin') {
    admin_page('list', ['title' => 'Статьи', 'list' => articles_all(false)]);
}

http_response_code(404);
admin_page('list', ['title' => 'Статьи', 'list' => articles_all(false)]);

/** Resize to max 1600px and save as WebP when GD supports it; otherwise keep the original format. */
function optimize_upload($tmp, array $info, $base, $ext)
{
    $max = 1600;
    if (function_exists('imagewebp')) {
        $src = $info['mime'] === 'image/png' ? @imagecreatefrompng($tmp)
             : ($info['mime'] === 'image/webp' ? @imagecreatefromwebp($tmp) : @imagecreatefromjpeg($tmp));
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            if ($w > $max) {
                $dst = imagecreatetruecolor($max, (int)round($h * $max / $w));
                imagealphablending($dst, false); imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $max, (int)round($h * $max / $w), $w, $h);
                imagedestroy($src); $src = $dst;
            }
            imagewebp($src, $base . '.webp', 82);
            imagedestroy($src);
            return $base . '.webp';
        }
    }
    move_uploaded_file($tmp, $base . '.' . $ext);
    return $base . '.' . $ext;
}
