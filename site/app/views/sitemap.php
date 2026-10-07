<?= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
$urls = ['/' => '1.0', '/uslugi' => '0.9', '/ceny' => '0.9', '/otzyvy' => '0.6', '/o-kompanii' => '0.5', '/kontakty' => '0.7', '/dezsredstva' => '0.6', '/blog' => '0.7', '/politika-konfidencialnosti' => '0.1'];
foreach ($services as $slug => $s) $urls['/uslugi/' . $slug] = '0.9';
foreach ($urls as $u => $prio) { if (has_kk($u)) $kkUrls['/kk' . ($u === '/' ? '' : $u)] = $prio; }
$urls += $kkUrls ?? [];
foreach ($urls as $u => $prio): ?>
  <url><loc><?= e(abs_url($u)) ?></loc><priority><?= $prio ?></priority></url>
<?php endforeach;
foreach ($articles as $a): ?>
  <url><loc><?= e(abs_url('/blog/' . $a['slug'])) ?></loc><lastmod><?= e(substr($a['updated_at'], 0, 10)) ?></lastmod><priority>0.6</priority></url>
<?php endforeach ?>
</urlset>
