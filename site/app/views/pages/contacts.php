<?php $c = cfg(); ?>
<section class="page-head">
  <div class="container">
    <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Контакты' => '/kontakty']]) ?>
    <h1>Контакты</h1>
  </div>
</section>
<section class="section">
  <div class="container two-col">
    <div class="contacts">
      <p class="contacts__phone"><a href="tel:<?= e($c['phone']) ?>"><?= e($c['phone_human']) ?></a></p>
      <p><a class="btn btn--wa" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">Написать в WhatsApp</a></p>
      <dl>
        <dt>Адрес</dt><dd><?= e($c['city']) ?>, <?= e($c['address']) ?></dd>
        <dt>График</dt><dd><?= e($c['hours']) ?>, без выходных</dd>
        <?php if ($c['email']): ?><dt>E-mail</dt><dd><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></dd><?php endif ?>
        <?php if ($c['bin']): ?><dt>Реквизиты</dt><dd><?= e($c['legal_name']) ?>, БИН <?= e($c['bin']) ?></dd><?php endif ?>
      </dl>
      <p><a class="btn btn--ghost" href="<?= e($c['gis_route']) ?>" target="_blank" rel="noopener">Проложить маршрут в 2ГИС</a></p>
    </div>
    <div class="map" data-lat="<?= e($c['geo']['lat']) ?>" data-lng="<?= e($c['geo']['lng']) ?>" data-title="<?= e($c['name']) ?>">
      <button type="button" class="map__load">Показать карту</button>
    </div>
  </div>
</section>
<?= view('partials/lead') ?>
