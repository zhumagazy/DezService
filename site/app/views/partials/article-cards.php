<div class="cards">
  <?php foreach ($list as $a): ?>
    <a class="card card--article" href="/blog/<?= e($a['slug']) ?>">
      <?php if ($a['cover']): ?><img src="<?= e($a['cover']) ?>" alt="" loading="lazy" width="400" height="240"><?php endif ?>
      <span class="card__body">
        <span class="card__title"><?= e($a['title']) ?></span>
        <span class="card__text"><?= e(excerpt($a, 140)) ?></span>
        <span class="card__meta"><?= e(date('d.m.Y', strtotime($a['published_at']))) ?></span>
      </span>
    </a>
  <?php endforeach ?>
</div>
