<div class="a-head">
  <h1>Статьи</h1>
  <a class="a-btn" href="/admin/edit">+ Новая статья</a>
</div>
<?php if (isset($_GET['deleted'])): ?><p class="a-ok">Статья удалена.</p><?php endif ?>
<?php if (!$list): ?>
  <div class="a-card"><p>Статей пока нет. Нажмите «Новая статья», чтобы написать первую.</p></div>
<?php else: ?>
<table class="a-table">
  <thead><tr><th>Заголовок</th><th>Статус</th><th>Дата публикации</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $a): ?>
    <tr>
      <td><a href="/admin/edit?id=<?= e($a['id']) ?>"><?= e($a['title']) ?></a><div class="a-muted">/blog/<?= e($a['slug']) ?></div></td>
      <td><?php if (article_is_live($a)): ?><span class="a-tag a-tag--live">Опубликована</span>
          <?php elseif ($a['status'] === 'published'): ?><span class="a-tag">Запланирована</span>
          <?php else: ?><span class="a-tag">Черновик</span><?php endif ?></td>
      <td><?= e(date('d.m.Y H:i', strtotime($a['published_at']))) ?></td>
      <td class="a-actions">
        <?php if (article_is_live($a)): ?><a href="/blog/<?= e($a['slug']) ?>" target="_blank">Открыть</a><?php endif ?>
        <form method="post" action="/admin/delete" onsubmit="return confirm('Удалить статью «<?= e(addslashes($a['title'])) ?>»?')">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e($a['id']) ?>">
          <button class="a-link-danger">Удалить</button>
        </form>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
<?php endif ?>
