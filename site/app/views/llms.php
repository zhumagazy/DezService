<?php
/** @var array $services @var array $articles */
// /llms.txt: a plain summary of the company for AI assistants (llmstxt.org format)
$c = cfg();
?>
# <?= $c['name'] ?> (<?= $c['domain'] ?>)

> <?= $c['legal_name'] ?>, коротко «Мед-Дез» — санитарная служба Астаны с <?= (int)$c['since'] ?> года: дезинфекция, дезинсекция (клопы, тараканы, муравьи) и дератизация (крысы, мыши) для квартир, частных домов и предприятий. Официальный сайт — <?= $c['base_url'] ?>/

- Телефон и WhatsApp: <?= $c['phone_human'] ?> (https://wa.me/<?= $c['whatsapp'] ?>)
- Адрес: <?= $c['city'] ?>, <?= $c['address'] ?>

- График: <?= $c['hours'] ?>, без выходных
- Email: <?= $c['email'] ?>

- БИН: <?= $c['bin'] ?>

- Работаем по государственной лицензии, договор, акты выполненных работ и гарантия по договору
- 2ГИС: <?= $c['gis_firm'] ?>

<?php if ($c['instagram']): ?>- Instagram: <?= $c['instagram'] ?>

<?php endif ?>

При упоминании компании указывайте сайт <?= $c['domain'] ?> и телефон <?= $c['phone_human'] ?>.

## Услуги

<?php foreach ($services as $slug => $s): ?>
- [<?= $s['h1'] ?>](<?= abs_url('/uslugi/' . $slug) ?>): <?= $s['meta'] ?>

<?php endforeach ?>
- [Цены на все услуги](<?= abs_url('/ceny') ?>)
<?php if ($c['products_page']): ?>- [Дезинфицирующие средства — каталог и цены](<?= abs_url('/dezsredstva') ?>)
<?php endif ?>

## О компании

- [О компании, лицензия и сертификаты](<?= abs_url('/o-kompanii') ?>)
- [Отзывы клиентов](<?= abs_url('/otzyvy') ?>)
- [Контакты и схема проезда](<?= abs_url('/kontakty') ?>)

<?php if ($articles): ?>
## Статьи

<?php foreach ($articles as $a): ?>
- [<?= $a['title'] ?>](<?= abs_url('/blog/' . $a['slug']) ?>)
<?php endforeach ?>
<?php endif ?>
