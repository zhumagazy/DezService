<?php $base = parse_url(cfg('base_url'), PHP_URL_HOST); ?>
<link rel="stylesheet" href="<?= asset('vendor/quill/quill.snow.css') ?>">
<form class="a-edit" method="post" action="/admin/edit<?= $a['id'] ? '?id=' . e($a['id']) : '' ?>" id="editForm">
  <div class="a-head">
    <h1><?= e($title) ?></h1>
    <div class="a-head__actions">
      <?php if ($a['id'] && article_is_live($a)): ?><a href="/blog/<?= e($a['slug']) ?>" target="_blank">Открыть на сайте</a><?php endif ?>
      <button class="a-btn">Сохранить</button>
    </div>
  </div>
  <?php if (isset($_GET['saved'])): ?><p class="a-ok">Сохранено.</p><?php endif ?>
  <?php foreach ($errors as $err): ?><p class="a-error"><?= e($err) ?></p><?php endforeach ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>" id="csrf">

  <div class="a-grid">
    <div class="a-col-main">
      <label>Заголовок статьи<input name="title" id="title" value="<?= e($a['title']) ?>" required maxlength="160"></label>
      <label>Коротко — прямой ответ в 2–3 предложениях <small>(показывается первым, его чаще всего цитируют поисковики и ИИ)</small>
        <textarea name="summary" rows="3" maxlength="600"><?= e($a['summary'] ?? '') ?></textarea></label>
      <div class="a-label">Текст статьи</div>
      <div id="editor"><?= $a['body'] ?></div>
      <textarea name="body" id="body" hidden></textarea>
      <p class="a-muted">Используйте «Заголовок 2» и «Заголовок 3» для разделов — это важно для SEO. Картинки вставляются кнопкой с изображением.</p>
      <label>Частые вопросы <small>(вопрос на первой строке, ответ на следующей, между парами — пустая строка)</small>
        <textarea name="faq" rows="8" placeholder="Сколько длится обработка?&#10;Квартира обрабатывается за 30–60 минут.&#10;&#10;Нужно ли уходить из дома?&#10;Да, на 2–3 часа."><?= e(faq_to_text($a['faq'] ?? [])) ?></textarea></label>
      <label>Шаги инструкции <small>(только для статей-инструкций: один шаг на строку — попадут в разметку HowTo)</small>
        <textarea name="howto" rows="5"><?= e(implode("\n", $a['howto'] ?? [])) ?></textarea></label>
    </div>

    <aside class="a-col-side">
      <div class="a-card">
        <h2>Публикация</h2>
        <label>Статус
          <select name="status">
            <option value="draft"<?= $a['status'] === 'draft' ? ' selected' : '' ?>>Черновик</option>
            <option value="published"<?= $a['status'] === 'published' ? ' selected' : '' ?>>Опубликовать</option>
          </select>
        </label>
        <label>Дата публикации<input type="datetime-local" name="published_at" value="<?= e(str_replace(' ', 'T', $a['published_at'])) ?>"></label>
        <p class="a-muted">Если указать дату в будущем, статья появится на сайте автоматически.</p>
      </div>

      <div class="a-card">
        <h2>Обложка</h2>
        <input type="hidden" name="cover" id="cover" value="<?= e($a['cover']) ?>">
        <img id="coverPreview" src="<?= e($a['cover']) ?>" alt=""<?= $a['cover'] ? '' : ' hidden' ?>>
        <label class="a-btn a-btn--ghost">Загрузить картинку<input type="file" accept="image/jpeg,image/png,image/webp" id="coverFile" hidden></label>
        <button type="button" class="a-link-danger" id="coverRemove"<?= $a['cover'] ? '' : ' hidden' ?>>Убрать</button>
      </div>

      <div class="a-card">
        <h2>SEO</h2>
        <label>Адрес страницы
          <span class="a-prefix"><span>/blog/</span><input name="slug" id="slug" value="<?= e($a['slug']) ?>" placeholder="заполнится из заголовка" pattern="[a-z0-9-]*"></span>
        </label>
        <label>Заголовок H1 <small>(если отличается от заголовка)</small><input name="h1" value="<?= e($a['h1']) ?>"></label>
        <label>Title для поисковиков <small class="a-count" data-for="meta_title" data-max="60"></small><input name="meta_title" id="meta_title" value="<?= e($a['meta_title']) ?>" maxlength="120"></label>
        <label>Description <small class="a-count" data-for="meta_description" data-max="160"></small><textarea name="meta_description" id="meta_description" rows="3" maxlength="300"><?= e($a['meta_description']) ?></textarea></label>
        <label>Анонс для списка статей<textarea name="excerpt" rows="3" maxlength="300"><?= e($a['excerpt']) ?></textarea></label>
        <div class="a-serp" aria-label="Так статья будет выглядеть в Google">
          <div class="a-serp__url"><?= e($base) ?> › blog › <span id="serpSlug"></span></div>
          <div class="a-serp__title" id="serpTitle"></div>
          <div class="a-serp__desc" id="serpDesc"></div>
        </div>
      </div>
    </aside>
  </div>
</form>
<script src="<?= asset('vendor/quill/quill.js') ?>"></script>
<script src="<?= asset('js/admin.js') ?>"></script>
