<?php
// Rebuild the bundled price list site/app/catalog.json from the supplier sheet exported as CSV.
// Usage: php scripts/import_catalog.php price.csv
// On the live site the same import is done in the admin panel: /admin/catalog.
require __DIR__ . '/../site/app/lib.php';
if (empty($argv[1])) exit("Usage: php scripts/import_catalog.php price.csv\n");
$cat = catalog_from_csv($argv[1]);
file_put_contents(__DIR__ . '/../site/app/catalog.json', json_encode($cat, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
echo count($cat) . ' categories, ' . catalog_count($cat) . " positions\n";
