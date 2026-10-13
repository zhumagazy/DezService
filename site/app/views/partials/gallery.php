<?php
/** Real photos and videos from our jobs; $class sets the section background */
$items = data('gallery');
$photos = array_values(array_filter($items, function ($g) { return empty($g['video']); }));
$videos = array_values(array_filter($items, function ($g) { return !empty($g['video']); }));
?>
<section class="<?= e($class ?? 'section') ?>" id="foto">
  <div class="container">
    <h2><?= e($title ?? 'Наша работа на объектах') ?></h2>
    <p class="muted">Реальные фото и видео нашей бригады — без стоков.</p>
    <?php if ($photos): ?>
      <ul class="gallery">
        <?php foreach ($photos as $i => $g): ?>
          <li class="gallery__item<?= $i === 0 ? ' gallery__item--wide' : '' ?>">
            <a href="<?= img($g['image']) ?>" data-lightbox>
              <img src="<?= e('/assets/img/' . $g['image'] . '-sm.webp') ?>" alt="<?= e($g['caption']) ?>" loading="lazy">
            </a>
            <span class="gallery__cap"><?= e($g['caption']) ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
    <?php if ($videos): ?>
      <ul class="videos">
        <?php foreach ($videos as $g): ?>
          <li>
            <video src="<?= e($g['video']) ?>" poster="<?= img($g['image']) ?>" controls preload="none" playsinline aria-label="<?= e($g['caption']) ?>"></video>
            <span><?= e($g['caption']) ?></span>
          </li>
        <?php endforeach ?>
      </ul>
    <?php endif ?>
  </div>
</section>
