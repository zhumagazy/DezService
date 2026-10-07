<section class="page-head">
  <div class="container">
    <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Дезсредства' => '/dezsredstva']]) ?>
    <h1>Дезинфицирующие средства</h1>
    <p class="page-head__lead">Сертифицированные средства для медицинских и детских учреждений, салонов красоты, общепита и гостиниц. Поможем подобрать средство и рассчитать расход.</p>
  </div>
</section>
<section class="section">
  <div class="container products">
    <?php foreach (data('products') as $p): ?>
      <article class="card product" id="<?= e($p['slug']) ?>">
        <img src="<?= img($p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy" width="240" height="240">
        <h2><?= e($p['name']) ?></h2>
        <p><?= e($p['text']) ?></p>
        <p class="muted">Фасовка: <?= e($p['pack']) ?></p>
        <a class="btn btn--ghost btn--block" href="<?= e(wa_link('Здравствуйте! Интересует ' . $p['name'] . '. Подскажите цену и наличие.')) ?>" target="_blank" rel="noopener">Узнать цену</a>
      </article>
    <?php endforeach ?>
  </div>
</section>
<?= view('partials/lead', ['title' => 'Подберём дезсредство под ваш объект']) ?>
