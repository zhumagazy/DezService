<?php /** $services, $exclude optional */ ?>
<div class="cards">
  <?php foreach ($services as $slug => $s): if (($exclude ?? '') === $slug) continue;
      $min = array_filter(array_column($s['prices'], 'price'), function ($p) { return $p !== null; }); ?>
    <a class="card card--service" href="/uslugi/<?= $slug ?>">
      <img src="<?= img($s['image']) ?>" alt="" loading="lazy" width="400" height="240">
      <span class="card__body">
        <span class="card__title"><?= e($s['menu']) ?></span>
        <span class="card__text"><?= e($s['meta']) ?></span>
        <span class="card__price"><?= $min ? price_label(min($min)) : 'Цена по запросу' ?> <span aria-hidden="true">→</span></span>
      </span>
    </a>
  <?php endforeach ?>
</div>
