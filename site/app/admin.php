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
