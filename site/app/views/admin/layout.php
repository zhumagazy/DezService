<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — <?= e(cfg('name')) ?></title>
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<header class="a-header">
  <a class="a-brand" href="/admin">Админ-панель · <?= e(cfg('name')) ?></a>
  <?php if (admin_logged_in()): ?>
    <nav>
      <a href="/admin">Статьи</a>
      <a href="/admin/edit">Новая статья</a>
      <a href="/admin/password">Пароль</a>
      <a href="/" target="_blank">Открыть сайт</a>
      <form method="post" action="/admin/logout"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button>Выйти</button></form>
    </nav>
  <?php endif ?>
</header>
<main class="a-main"><?= $body ?></main>
</body>
</html>
