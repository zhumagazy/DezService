<?php
$items = $g['items'];
$items[] = ['name' => '', 'shelf' => '', 'composition' => [], 'variants' => [], 'new' => true];  // block for a new product
?>
<form method="post" action="/admin/catalog/edit?cat=<?= e($g['slug']) ?>" class="a-catalog">
  <div class="a-head">
    <h1><?= e($g['title']) ?></h1>
    <div class="a-head__actions">
      <a href="/admin/catalog">← Все категории</a>
      <a href="/dezsredstva#<?= e($g['slug']) ?>" target="_blank">На сайте</a>
      <button class="a-btn">Сохранить</button>
    </div>
  </div>
  <?php if (isset($_GET['saved'])): ?><p class="a-ok">Сохранено, изменения уже на сайте.</p><?php endif ?>
  <?php foreach (array_unique($errors) as $er): ?><p class="a-error"><?= e($er) ?></p><?php endforeach ?>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <div class="a-card a-row2">
    <label>Название категории<input name="title" value="<?= e($g['title']) ?>" required maxlength="120"></label>
    <label>В меню каталога<input name="short" value="<?= e($g['short'] ?? '') ?>" maxlength="40"></label>
  </div>

  <?php foreach ($items as $i => $it): $new = !empty($it['new']); $rows = $it['variants']; $rows[] = []; if ($new) { $rows[] = []; $rows[] = []; } ?>
    <fieldset class="a-card a-product<?= $new ? ' a-product--new' : '' ?>">
      <legend><?= $new ? '+ Новое средство' : e($it['name']) ?></legend>
      <?php if ($new): ?><p class="a-muted">Заполните, чтобы добавить средство в эту категорию. Пустой блок не сохраняется.</p><?php endif ?>
      <div class="a-row2">
        <label>Название<input name="items[<?= $i ?>][name]" value="<?= e($it['name']) ?>" maxlength="120" placeholder="Например: Алмадез-Ликвид"></label>
        <label>Срок годности<input name="items[<?= $i ?>][shelf]" value="<?= e($it['shelf']) ?>" maxlength="160" placeholder="Срок годности 6 лет, рабочего раствора 8 суток"></label>
      </div>
      <label>Состав <small>(каждый компонент с новой строки)</small>
        <textarea name="items[<?= $i ?>][composition]" rows="<?= max(2, count($it['composition'])) ?>"><?= e(implode("\n", $it['composition'])) ?></textarea></label>
      <table class="a-variants">
        <thead><tr><th>Артикул</th><th>Фасовка</th><th>Ед.</th><th>Цена, ₸ с НДС</th><th>Под заказ</th><th>Удалить</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $j => $v): $v += ['sku' => '', 'size' => '', 'unit' => 'шт', 'price' => '', 'to_order' => false]; $empty = $v['sku'] === '' && $v['size'] === ''; ?>
          <tr<?= $empty ? ' class="a-variants__new"' : '' ?>>
            <td><input name="items[<?= $i ?>][variants][<?= $j ?>][sku]" value="<?= e($v['sku']) ?>" maxlength="40"<?= $empty ? ' placeholder="новая фасовка"' : '' ?>></td>
            <td><input name="items[<?= $i ?>][variants][<?= $j ?>][size]" value="<?= e($v['size']) ?>" maxlength="120"<?= $empty ? ' placeholder="1 л · крышка"' : '' ?>></td>
            <td><input name="items[<?= $i ?>][variants][<?= $j ?>][unit]" value="<?= e($v['unit']) ?>" maxlength="10"></td>
            <td><input name="items[<?= $i ?>][variants][<?= $j ?>][price]" value="<?= e((string)$v['price']) ?>" inputmode="numeric" maxlength="9"></td>
            <td class="a-c"><input type="checkbox" name="items[<?= $i ?>][variants][<?= $j ?>][to_order]" value="1"<?= $v['to_order'] ? ' checked' : '' ?> aria-label="Под заказ"></td>
            <td class="a-c"><?php if (!$empty): ?><input type="checkbox" name="items[<?= $i ?>][variants][<?= $j ?>][delete]" value="1" aria-label="Удалить фасовку"><?php endif ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
      <?php if (!$new): ?>
        <label class="a-inline a-danger"><input type="checkbox" name="items[<?= $i ?>][delete]" value="1"> Удалить средство целиком</label>
      <?php endif ?>
    </fieldset>
  <?php endforeach ?>

  <div class="a-head">
    <label class="a-inline a-danger"><input type="checkbox" name="delete_category" value="1" onchange="if(this.checked&&!confirm('Удалить всю категорию со всеми товарами?'))this.checked=false"> Удалить категорию</label>
    <button class="a-btn">Сохранить</button>
  </div>
</form>
