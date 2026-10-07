<form class="a-card a-narrow" method="post" action="/admin/password">
  <h1>Смена пароля</h1>
  <?php if ($error): ?><p class="a-error"><?= e($error) ?></p><?php endif ?>
  <?php if ($ok): ?><p class="a-ok"><?= e($ok) ?></p><?php endif ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>Текущий пароль<input type="password" name="current" required autocomplete="current-password"></label>
  <label>Новый пароль (от 10 символов)<input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
  <button class="a-btn">Сохранить</button>
</form>
