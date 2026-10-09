<div class="a-head">
  <h1>Дезсредства</h1>
  <div class="a-head__actions"><a href="/dezsredstva" target="_blank">Открыть на сайте</a></div>
</div>
<?php if ($flash): ?><p class="a-ok"><?= e($flash) ?></p><?php endif ?>
<?php if ($err): ?><p class="a-error"><?= e($err) ?></p><?php endif ?>

<div class="a-grid">
  <div>
    <table class="a-table">
      <thead><tr><th>Категория</th><th>Средств</th><th>Позиций</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cat as $g): ?>
        <tr>
          <td><a href="/admin/catalog/edit?cat=<?= e($g['slug']) ?>"><?= e($g['title']) ?></a><div class="a-muted">В меню каталога: <?= e($g['short'] ?? $g['title']) ?></div></td>
          <td><?= count($g['items']) ?></td>
          <td><?= catalog_count([$g]) ?></td>
          <td class="a-actions"><a href="/admin/catalog/edit?cat=<?= e($g['slug']) ?>">Цены и товары</a></td>
        </tr>
      <?php endforeach ?>
      </tbody>
    </table>
    <p class="a-muted">Всего позиций: <?= catalog_count($cat) ?>. Чтобы поменять цену, название или фасовку, откройте категорию.</p>

    <form class="a-card" method="post" action="/admin/catalog/category">
      <h2>Новая категория</h2>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label>Название<input name="title" required maxlength="120" placeholder="Например: Средства для бассейнов"></label>
      <label>Короткое название для меню каталога <small>(необязательно)</small><input name="short" maxlength="40"></label>
      <button class="a-btn">Создать и добавить товары</button>
    </form>
  </div>

  <aside>
    <form class="a-card" method="post" action="/admin/catalog/upload" enctype="multipart/form-data" onsubmit="return confirm('Заменить весь каталог данными из файла? Текущую версию можно будет вернуть кнопкой «Откатить».')">
      <h2>Загрузить прайс целиком</h2>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <p class="a-muted">Откройте Google-таблицу с прайсом, лист «АЛМАДЕЗ» → Файл → Скачать → <b>CSV</b>. Загрузите файл сюда: каталог на сайте полностью заменится, ручные правки тоже.</p>
      <p class="a-muted">Колонки должны идти как сейчас: артикул, (пусто), наименование, фото, состав, срок годности, ед. изм., цена с НДС. Строка без артикула и цены — название категории. «ПОД ЗАКАЗ» в названии превращается в значок.</p>
      <label>Файл CSV<input type="file" name="csv" accept=".csv,text/csv" required></label>
      <button class="a-btn">Загрузить</button>
    </form>
    <?php if ($has_prev): ?>
      <form class="a-card" method="post" action="/admin/catalog/rollback" onsubmit="return confirm('Вернуть каталог к состоянию до последнего сохранения?')">
        <h2>Отменить последнее изменение</h2>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <p class="a-muted">Вернёт каталог к состоянию до последней загрузки или правки.</p>
        <button class="a-btn a-btn--ghost">Откатить</button>
      </form>
    <?php endif ?>
  </aside>
</div>
