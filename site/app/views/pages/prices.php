<section class="page-head">
  <div class="container">
    <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Цены' => '/ceny']]) ?>
    <h1>Цены на обработку в Астане</h1>
    <p class="page-head__lead">Стоимость зависит от площади, вида вредителей и степени заражения. Назовём точную цену после 3 вопросов — бесплатно и без обязательств.</p>
  </div>
</section>
<section class="section">
  <div class="container price-grid">
    <?php foreach ($services as $slug => $s): ?>
      <div class="card price-card">
        <h2><a href="/uslugi/<?= $slug ?>"><?= e($s['menu']) ?></a></h2>
        <table class="prices">
          <tbody>
            <?php foreach ($s['prices'] as $p): ?>
              <tr><th scope="row"><?= e($p['name']) ?></th><td><?= e(price_label($p['price'])) ?></td></tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    <?php endforeach ?>
  </div>
</section>
<?= view('partials/lead') ?>
