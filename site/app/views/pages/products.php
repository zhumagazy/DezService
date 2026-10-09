<?php $cat = catalog(); $total = 0; foreach ($cat as $g) foreach ($g['items'] as $i) $total += count($i['variants']); ?>
<section class="page-head">
  <div class="container">
    <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Дезсредства' => '/dezsredstva']]) ?>
    <h1>Дезинфицирующие средства</h1>
    <p class="page-head__lead">Антисептики, концентраты, средства для стерилизации, хлорные таблетки, дезинфицирующее мыло и салфетки для клиник, детских садов, салонов красоты, общепита и офисов. <?= $total ?> позиций с ценами.</p>
    <p class="muted">Цены указаны с НДС. Наличие и срок поставки позиций «под заказ» уточняйте у менеджера. Добавьте нужные позиции в заказ — он придёт нам одним сообщением в WhatsApp.</p>
  </div>
</section>

<div class="catalog-bar">
  <div class="container catalog-bar__in">
    <label class="catalog-search">
      <span class="sr-only">Поиск по каталогу</span>
      <input type="search" placeholder="Поиск: название, объём или артикул" data-catalog-search>
    </label>
    <nav class="catalog-nav" aria-label="Категории">
      <?php foreach ($cat as $g): ?><a href="#<?= e($g['slug']) ?>"><?= e($g['short'] ?? $g['title']) ?></a><?php endforeach ?>
    </nav>
  </div>
</div>

<section class="section catalog" data-wa="<?= e(cfg('whatsapp')) ?>">
  <div class="container">
    <?php foreach ($cat as $g): ?>
      <div class="catalog__group" id="<?= e($g['slug']) ?>">
        <h2><?= e($g['title']) ?></h2>
        <div class="catalog__grid">
          <?php foreach ($g['items'] as $it): ?>
            <article class="card sku" id="<?= e($it['slug']) ?>">
              <?php if (!empty($it['image'])): ?><img class="sku__img" src="<?= e($it['image']) ?>" alt="<?= e($it['name']) ?>" loading="lazy" width="400" height="200"><?php endif ?>
              <h3><?= e($it['name']) ?></h3>
              <?php if ($it['shelf']): ?><p class="muted"><?= e($it['shelf']) ?></p><?php endif ?>
              <?php if ($it['composition']): ?>
                <details class="sku__comp">
                  <summary>Состав</summary>
                  <ul><?php foreach ($it['composition'] as $line): ?><li><?= e($line) ?></li><?php endforeach ?></ul>
                </details>
              <?php endif ?>
              <ul class="sku__variants">
                <?php foreach ($it['variants'] as $v): ?>
                  <li data-sku="<?= e($v['sku']) ?>" data-name="<?= e($it['name'] . ', ' . ($v['size'] ?: $v['unit'])) ?>" data-price="<?= (int)$v['price'] ?>" data-search="<?= e(mb_strtolower($it['name'] . ' ' . $v['title'] . ' ' . $v['sku'])) ?>">
                    <span class="sku__size"><?= e($v['size'] ?: $v['unit']) ?><?php if ($v['to_order']): ?> <em class="tag">под заказ</em><?php endif ?><small><?= e($v['sku']) ?></small></span>
                    <b class="sku__price"><?= money($v['price']) ?></b>
                    <a class="sku__add" href="<?= e(wa_link('Здравствуйте! Хочу заказать: ' . $it['name'] . ', ' . $v['size'] . ' (арт. ' . $v['sku'] . ').')) ?>" target="_blank" rel="noopener" aria-label="Добавить в заказ: <?= e($v['title']) ?>">+</a>
                  </li>
                <?php endforeach ?>
              </ul>
            </article>
          <?php endforeach ?>
        </div>
      </div>
    <?php endforeach ?>
    <p class="catalog__empty muted" hidden>Ничего не нашлось. Напишите нам в WhatsApp — подберём замену.</p>

  </div>
</section>

<div class="cart" hidden>
  <div class="cart__panel" id="cart-panel" hidden>
    <div class="cart__head"><b>Ваш заказ</b><button type="button" class="cart__close" data-cart-toggle aria-label="Свернуть">×</button></div>
    <ul class="cart__list"></ul>
    <p class="muted">Итог предварительный, менеджер подтвердит наличие и стоимость доставки.</p>
  </div>
  <div class="cart__bar">
    <button type="button" class="cart__sum" data-cart-toggle aria-controls="cart-panel" aria-expanded="false"><span data-cart-count></span><b data-cart-total></b></button>
    <button type="button" class="btn btn--wa" data-cart-send>Отправить в WhatsApp</button>
  </div>
</div>

<?= view('partials/lead', ['title' => 'Подберём дезсредство под ваш объект']) ?>
