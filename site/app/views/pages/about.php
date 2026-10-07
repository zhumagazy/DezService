<?php $c = cfg(); ?>
<section class="page-head">
  <div class="container">
    <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'О компании' => '/o-kompanii']]) ?>
    <h1>О компании <?= e($c['name']) ?></h1>
    <p class="page-head__lead">Работаем в Астане с <?= (int)$c['since'] ?> года. Обслуживаем квартиры и частные дома, а также предприятия, торговые сети, гостиницы и учреждения по договору.</p>
  </div>
</section>
<?= view('partials/advantages') ?>
<?= view('partials/trust') ?>
<?= view('partials/lead') ?>
