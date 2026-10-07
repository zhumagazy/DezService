<form class="a-card a-narrow" method="post" action="/admin/setup">
  <h1>Первый вход</h1>
  <p>Ключ установки лежит на хостинге в файле <code>storage/setup-key.php</code> (откройте его в файловом менеджере Plesk — ключ после слова <code>"key"</code>). Он нужен один раз, чтобы никто посторонний не создал пароль раньше вас.</p>
  <?php if ($error): ?><p class="a-error"><?= e($error) ?></p><?php endif ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>Ключ установки<input name="key" required autocomplete="off"></label>
  <label>Логин<input name="login" value="admin" required autocomplete="username"></label>
  <label>Пароль (от 10 символов)<input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
  <button class="a-btn">Создать доступ</button>
</form>
