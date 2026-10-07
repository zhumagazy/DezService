<section class="page-head">
  <div class="container">
    <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Услуги' => '/uslugi']]) ?>
    <h1>Услуги санитарной обработки в Астане</h1>
    <p class="page-head__lead">Обрабатываем квартиры, частные дома, офисы и предприятия. Выезжаем в день обращения, работаем по договору с гарантией.</p>
  </div>
</section>
<section class="section">
  <div class="container">
    <?= view('partials/service-cards', ['services' => $services]) ?>
  </div>
</section>
<?= view('partials/steps') ?>
<?= view('partials/lead') ?>
