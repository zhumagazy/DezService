<?php /** $items: [label => href], last item is current */ ?>
<nav class="crumbs" aria-label="Хлебные крошки">
  <ol>
    <?php $i = 0; $n = count($items); foreach ($items as $label => $href): $i++; ?>
      <li><?php if ($i < $n): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif ?></li>
    <?php endforeach ?>
  </ol>
</nav>
