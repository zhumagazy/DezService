<form class="a-card a-narrow" method="post" action="/admin/login">
  <h1>Вход</h1>
  <?php if ($error): ?><p class="a-error"><?= e($error) ?></p><?php endif ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>Логин<input name="login" required autocomplete="username" autofocus></label>
  <label>Пароль<input type="password" name="password" required autocomplete="current-password"></label>
  <button class="a-btn">Войти</button>
</form>
