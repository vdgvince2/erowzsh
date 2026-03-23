<?php
/*
  JSON-LD for product list pages (categories & keywords)
  - CollectionPage > ItemList (each item wrapped in ListItem)
  - BreadcrumbList
*/

// ── CollectionPage + ItemList ──────────────────────────────────────────────────
$itemListElements = [];
$i = 1;
foreach ($products as $prod) {
    if ($prod['price'] == "") $prod['price'] = "10.00";
    $photoURL = $rootDomain . $base . "image.php?url=" . base64_encode($prod['photo']);
    $prodUrl  = !empty($prod['url']) ? $prod['url'] : $SERVER_PageFullURL . '#' . $prod['id'];

    $itemListElements[] = [
        '@type'    => 'ListItem',
        'position' => $i,
        'item'     => [
            '@type' => 'Product',
            'name'  => strip_tags($prod['title_original']),
            'image' => $photoURL,
            'sku'   => 'SH-' . $prod['id'],
            'offers' => [
                '@type'        => 'Offer',
                'priceCurrency' => $priceCurrencySchema,
                'price'        => $prod['price'],
                'availability' => 'https://schema.org/InStock',
                'url'          => $prodUrl,
            ],
        ],
    ];
    $i++;
}

$collectionPage = [
    '@context'    => 'https://schema.org',
    '@type'       => 'CollectionPage',
    'url'         => $SERVER_PageFullURL,
    'name'        => strip_tags($pageTitle),
    'description' => strip_tags($pageTitle) . ' : ' . strip_tags($additionnalMetaDesc),
    'inLanguage'  => strtolower($mainLanguage),
    'mainEntity'  => [
        '@type'           => 'ItemList',
        'name'            => strip_tags($pageTitle),
        'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
        'numberOfItems'   => count($products),
        'itemListElement' => $itemListElements,
    ],
];

echo '<script type="application/ld+json">' . "\n";
echo json_encode($collectionPage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
echo "\n</script>";


// ── BreadcrumbList ─────────────────────────────────────────────────────────────
$bcItems = [];

// Position 1 — Home
$bcItems[] = [
    'pos'  => 1,
    'name' => $breadcrumb_home ?? 'Home',
    'url'  => $rootDomain . $base,
];

// Position 2 — Category (keyword pages only, when $rowCategory is set)
if (!isset($_GET['categ']) && !empty($rowCategory)) {
    $bcItems[] = [
        'pos'  => 2,
        'name' => $rowCategory['name'],
        'url'  => $rootDomain . $base . 's' . $rowCategory['slug_path'],
    ];
}

// Position 2 or 3 — Current page
$bcItems[] = [
    'pos'  => count($bcItems) + 1,
    'name' => strip_tags($pageTitle),
    'url'  => $SERVER_PageFullURL,
];

$bcElements = [];
foreach ($bcItems as $bc) {
    $bcElements[] = [
        '@type'    => 'ListItem',
        'position' => $bc['pos'],
        'name'     => $bc['name'],
        'item'     => $bc['url'],
    ];
}

$breadcrumb = [
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => $bcElements,
];

echo "\n" . '<script type="application/ld+json">' . "\n";
echo json_encode($breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
echo "\n</script>";
?>
