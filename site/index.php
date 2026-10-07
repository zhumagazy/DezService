<?php
// Front controller: every request that is not a real file lands here.
declare(strict_types=1);
date_default_timezone_set('Asia/Almaty');
mb_internal_encoding('UTF-8');
require __DIR__ . '/app/lib.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
// canonical form: no trailing slash except root
if ($path !== '/' && substr($path, -1) === '/') redirect(rtrim($path, '/'), 301);

if ($path === '/admin' || strpos($path, '/admin/') === 0) {
    require __DIR__ . '/app/admin.php';
    exit;
}

$services = data('services');

if ($path === '/') {
    render_page([
        'title' => 'Дезинфекция, дезинсекция и дератизация в Астане — ' . cfg('name'),
        'description' => 'Уничтожение клопов, тараканов, крыс и мышей, дезинфекция помещений в Астане. Выезд в день обращения, гарантия по договору, работаем с ' . cfg('since') . ' года.',
        'canonical' => '/',
        'schema' => [schema_business(), schema_faq(data('faq'))],
        'body' => view('pages/home', ['services' => $services, 'articles' => array_slice(articles_all(), 0, 3)]),
    ]);
} elseif ($path === '/uslugi') {
    render_page([
        'title' => 'Услуги и цены — ' . cfg('name'),
        'description' => 'Все услуги санитарной обработки в Астане: клопы, тараканы, муравьи, грызуны, дезинфекция и обслуживание предприятий по договору.',
        'canonical' => '/uslugi',
        'schema' => [schema_breadcrumbs(['Главная' => '/', 'Услуги' => '/uslugi'])],
        'body' => view('pages/services', ['services' => $services]),
    ]);
} elseif (preg_match('#^/uslugi/([a-z0-9-]+)$#', $path, $m) && isset($services[$m[1]])) {
    $s = $services[$m[1]];
    render_page([
        'title' => $s['title'] . ' — цены, гарантия | ' . cfg('name'),
        'description' => $s['meta'],
        'canonical' => '/uslugi/' . $m[1],
        'image' => img($s['image']),
        'schema' => [
            [
                '@context' => 'https://schema.org', '@type' => 'Service', 'name' => $s['h1'], 'description' => $s['meta'],
                'provider' => ['@id' => cfg('base_url') . '/#business'], 'areaServed' => cfg('city'),
            ],
            schema_faq($s['faq']),
            schema_breadcrumbs(['Главная' => '/', 'Услуги' => '/uslugi', $s['menu'] => '/uslugi/' . $m[1]]),
        ],
        'body' => view('pages/service', ['slug' => $m[1], 's' => $s, 'services' => $services]),
    ]);
} elseif ($path === '/ceny') {
    render_page([
        'title' => 'Цены на дезинсекцию, дератизацию и дезинфекцию в Астане',
        'description' => 'Стоимость обработки квартир, домов и предприятий от клопов, тараканов, грызунов и микробов. Бесплатный расчёт за 5 минут.',
        'canonical' => '/ceny',
        'schema' => [schema_breadcrumbs(['Главная' => '/', 'Цены' => '/ceny'])],
        'body' => view('pages/prices', ['services' => $services]),
    ]);
} elseif ($path === '/otzyvy') {
    render_page([
        'title' => 'Отзывы клиентов — ' . cfg('name'),
        'description' => 'Отзывы клиентов о дезинсекции, дератизации и дезинфекции от компании ' . cfg('name') . ' в Астане.',
        'canonical' => '/otzyvy',
        'body' => view('pages/reviews'),
    ]);
} elseif ($path === '/o-kompanii') {
    render_page([
        'title' => 'О компании — ' . cfg('name'),
        'description' => 'Санитарная служба в Астане с ' . cfg('since') . ' года: лицензия, сертификаты, клиенты и награды.',
        'canonical' => '/o-kompanii',
        'body' => view('pages/about'),
    ]);
} elseif ($path === '/kontakty') {
    render_page([
        'title' => 'Контакты — ' . cfg('name'),
        'description' => 'Адрес, телефон и график работы. ' . cfg('address') . ', ' . cfg('city') . '.',
        'canonical' => '/kontakty',
        'schema' => [schema_business()],
        'body' => view('pages/contacts'),
    ]);
} elseif ($path === '/dezsredstva') {
    render_page([
        'title' => 'Дезинфицирующие средства оптом и в розницу в Астане',
        'description' => 'Аламинол, Акваминол, Бианол, Макси-Дез и другие дезсредства для медицинских, детских и пищевых учреждений. Консультация по подбору.',
        'canonical' => '/dezsredstva',
        'body' => view('pages/products'),
    ]);
} elseif ($path === '/politika-konfidencialnosti') {
    render_page([
        'title' => 'Политика конфиденциальности',
        'description' => 'Как мы обрабатываем и защищаем персональные данные посетителей сайта.',
        'canonical' => '/politika-konfidencialnosti',
        'body' => view('pages/privacy'),
    ]);
} elseif ($path === '/blog') {
    $all = articles_all();
    $per = cfg('articles_per_page');
    $pages = max(1, (int)ceil(count($all) / $per));
    $n = max(1, (int)($_GET['page'] ?? 1));
    if ($n > $pages) not_found();
    render_page([
        'title' => 'Полезные статьи о борьбе с вредителями' . ($n > 1 ? " — страница $n" : ''),
        'description' => 'Советы специалистов: как избавиться от клопов, тараканов, грызунов и плесени, как подготовить квартиру к обработке.',
        'canonical' => '/blog' . ($n > 1 ? '?page=' . $n : ''),
        'body' => view('pages/blog', ['list' => array_slice($all, ($n - 1) * $per, $per), 'n' => $n, 'pages' => $pages]),
    ]);
} elseif (preg_match('#^/blog/([a-z0-9-]+)$#', $path, $m) && ($a = article_by_slug($m[1]))) {
    render_page([
        'title' => $a['meta_title'] ?: $a['title'],
        'description' => $a['meta_description'] ?: excerpt($a, 160),
        'canonical' => '/blog/' . $a['slug'],
        'image' => $a['cover'] ?: null,
        'og_type' => 'article',
        'schema' => [
            [
                '@context' => 'https://schema.org', '@type' => 'Article',
                'headline' => $a['title'], 'description' => excerpt($a, 160),
                'datePublished' => str_replace(' ', 'T', $a['published_at']) . ':00+05:00',
                'dateModified' => str_replace(' ', 'T', $a['updated_at']) . ':00+05:00',
                'image' => $a['cover'] ? abs_url($a['cover']) : abs_url(img('logo')),
                'author' => ['@type' => 'Organization', 'name' => cfg('name')],
                'publisher' => ['@id' => cfg('base_url') . '/#business'],
                'mainEntityOfPage' => abs_url('/blog/' . $a['slug']),
            ],
            schema_breadcrumbs(['Главная' => '/', 'Статьи' => '/blog', $a['title'] => '/blog/' . $a['slug']]),
        ],
        'body' => view('pages/article', ['a' => $a, 'services' => $services]),
    ]);
} elseif ($path === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    echo view('sitemap', ['services' => $services, 'articles' => articles_all()]);
} elseif ($path === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nDisallow: /admin\nDisallow: /storage/\nDisallow: /*?\nAllow: /blog?page=\n\nSitemap: " . abs_url('/sitemap.xml') . "\n";
} else {
    not_found();
}
