<?php /** $faq: list of [q, a]; $title optional */ ?>
<section class="section">
  <div class="container container--narrow">
    <h2><?= e($title ?? 'Частые вопросы') ?></h2>
    <div class="faq">
      <?php foreach ($faq as $f): ?>
        <details>
          <summary><?= e($f['q']) ?></summary>
          <p><?= e($f['a']) ?></p>
        </details>
      <?php endforeach ?>
    </div>
  </div>
</section>
