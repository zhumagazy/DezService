<section class="page-head">
  <div class="container">
    <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Статьи' => '/blog']]) ?>
    <h1>Полезные статьи</h1>
    <p class="page-head__lead">Советы специалистов: как распознать вредителей, подготовиться к обработке и не допустить их возвращения.</p>
  </div>
</section>
<section class="section">
  <div class="container">
    <?php if ($list): ?>
      <?= view('partials/article-cards', ['list' => $list]) ?>
      <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Страницы">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="/blog<?= $i > 1 ? '?page=' . $i : '' ?>"<?= $i === $n ? ' aria-current="page"' : '' ?>><?= $i ?></a>
          <?php endfor ?>
        </nav>
      <?php endif ?>
    <?php else: ?>
      <p>Статьи скоро появятся.</p>
    <?php endif ?>
  </div>
</section>
