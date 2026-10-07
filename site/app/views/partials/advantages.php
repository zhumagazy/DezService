<?php
$icons = [
  'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
  'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  'leaf'   => '<path d="M5 19c8 0 14-6 14-14-8 0-14 6-14 14z"/><path d="M5 19l7-7"/>',
  'doc'    => '<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/>',
];
?>
<section class="section">
  <div class="container">
    <ul class="features">
      <?php foreach (data('advantages') as $a): ?>
        <li class="feature">
          <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons[$a['icon']] ?></svg>
          <h3><?= e($a['title']) ?></h3>
          <p><?= e($a['text']) ?></p>
        </li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
