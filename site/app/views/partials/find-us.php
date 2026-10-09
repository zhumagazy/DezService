<?php $c = cfg(); ?>
<section class="section" id="kak-nas-nayti">
  <div class="container two-col">
    <div class="contacts">
      <h2>Как нас найти</h2>
      <dl>
        <dt>Адрес</dt><dd><?= e($c['city']) ?>, <?= e($c['address']) ?></dd>
        <dt>График</dt><dd><?= e($c['hours']) ?>, без выходных</dd>
        <dt>Телефон</dt><dd><a href="tel:<?= e($c['phone']) ?>"><?= e($c['phone_human']) ?></a></dd>
      </dl>
      <p><a class="btn btn--ghost" href="<?= e($c['gis_route']) ?>" target="_blank" rel="noopener">Проложить маршрут в 2ГИС</a></p>
    </div>
    <?= view('partials/map') ?>
  </div>
</section>
