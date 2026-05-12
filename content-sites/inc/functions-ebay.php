<?php
/**
 * Content-Sites — eBay helpers
 *
 * Étend le crawler eBay existant (/SH/scripts/crawler/ebay_browse_crawler.php)
 * en réutilisant get_access_token() et browse_search() déjà définis.
 * Ce fichier ne redéfinit que ce dont content-sites a besoin en plus.
 */

// Charge le crawler de base (définit get_access_token, browse_search)
require_once __DIR__ . '/../../scripts/crawler/ebay_browse_crawler.php';

// stub pour les contextes qui ne chargent pas inc/functions.php
if (!function_exists('log_local_write')) {
    function log_local_write($line, $file = 'debug.log'): void {}
}

/**
 * Remplace le suffixe de taille dans une URL eBay pour obtenir une image HD.
 * Ex: s-l140.jpg → s-l500.jpg
 */
function cs_ebay_hd_image(string $url, int $size = 500): string {
    if (empty($url)) return $url;
    return preg_replace('/s-l\d+(\.\w+)$/i', "s-l{$size}$1", $url);
}

/**
 * Récupère N produits eBay pour une sous-niche et les formate
 * pour l'insertion en DB et l'intégration dans l'article.
 *
 * @param string $query          Requête eBay (ebay_query de la sous-niche)
 * @param string $marketplace    Ex: EBAY_GB, EBAY_FR
 * @param int    $limit          Nombre de produits souhaités
 * @param string $currency       Symbole devise pour affichage
 * @return array                 Liste de produits normalisés
 */
function cs_fetch_ebay_products(
    string $query,
    string $marketplace,
    int    $limit = CS_EBAY_PRODUCTS_PER_ARTICLE,
    string $currency = '£'
): array {
    $raw = browse_search($query, $marketplace, $limit, 0, [
        'filter' => 'buyingOptions:{FIXED_PRICE}',
        'sort'   => '-watchCount',
    ]);

    if (isset($raw['error']) || empty($raw['itemSummaries'])) {
        return [];
    }

    $products = [];
    foreach ($raw['itemSummaries'] as $item) {
        $price = (float) ($item['price']['value'] ?? 0);
        if ($price <= 0) continue;

        $imageUrl = cs_ebay_hd_image(
            $item['image']['imageUrl']
            ?? $item['thumbnailImages'][0]['imageUrl']
            ?? ''
        );

        // URL affiliée eBay Partner Network
        $ebayUrl = $item['itemWebUrl'] ?? '';

        $products[] = [
            'ebay_item_id' => $item['itemId'] ?? '',
            'title'        => $item['title'] ?? '',
            'price'        => $price,
            'currency'     => $currency,
            'image_url'    => $imageUrl,
            'ebay_url'     => $ebayUrl,
        ];
    }

    return array_slice($products, 0, $limit);
}

/**
 * Insère les produits en DB et retourne leurs IDs.
 *
 * @param PDO   $pdo
 * @param int   $articleId
 * @param array $products  Résultat de cs_fetch_ebay_products()
 */
function cs_save_article_products(PDO $pdo, int $articleId, array $products): void
{
    $stmt = $pdo->prepare('
        INSERT INTO article_products
            (article_id, ebay_item_id, title, price, currency, image_url, ebay_url, position)
        VALUES
            (:article_id, :ebay_item_id, :title, :price, :currency, :image_url, :ebay_url, :position)
        ON DUPLICATE KEY UPDATE
            title     = VALUES(title),
            price     = VALUES(price),
            image_url = VALUES(image_url),
            ebay_url  = VALUES(ebay_url)
    ');

    foreach ($products as $pos => $p) {
        $stmt->execute([
            ':article_id'  => $articleId,
            ':ebay_item_id'=> $p['ebay_item_id'],
            ':title'       => $p['title'],
            ':price'       => $p['price'],
            ':currency'    => $p['currency'],
            ':image_url'   => $p['image_url'],
            ':ebay_url'    => $p['ebay_url'],
            ':position'    => $pos,
        ]);
    }
}

/**
 * Charge les produits existants d'un article depuis la DB.
 */
function cs_get_article_products(PDO $pdo, int $articleId): array
{
    $stmt = $pdo->prepare('
        SELECT * FROM article_products
        WHERE article_id = :id
        ORDER BY position ASC
    ');
    $stmt->execute([':id' => $articleId]);
    return $stmt->fetchAll();
}

/**
 * Récupère les signaux marché eBay pour une sous-niche (avec cache JSON 1h).
 *
 * Retourne un tableau avec :
 *   watch_total, bid_total, free_ship_pct, avg_discount_pct,
 *   top_rated_count, listings_count, condition_breakdown (array cond→pct)
 * Retourne null si l'API est inaccessible.
 */
function cs_fetch_market_pulse(string $ebayQuery, string $marketplace, int $limit = 15): ?array
{
    $cacheDir  = __DIR__ . '/../data';
    $cacheKey  = 'market_pulse_' . md5($ebayQuery . '_' . $marketplace) . '.json';
    $cacheFile = $cacheDir . '/' . $cacheKey;
    $cacheTTL  = 3600;

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) return $cached;
    }

    $raw = browse_search($ebayQuery, $marketplace, $limit, 0, [
        'filter' => 'buyingOptions:{FIXED_PRICE}',
        'sort'   => '-watchCount',
    ]);

    if (isset($raw['error']) || empty($raw['itemSummaries'])) return null;

    $items = $raw['itemSummaries'];
    $total = count($items);

    $watchTotal    = 0;
    $bidTotal      = 0;
    $freeShipCount = 0;
    $discountSum   = 0.0;
    $discountCount = 0;
    $topRatedCount = 0;
    $conditions    = [];

    foreach ($items as $item) {
        $watchTotal    += (int)($item['watchCount'] ?? 0);
        $bidTotal      += (int)($item['bidCount']   ?? 0);

        // livraison gratuite
        $shipCost = isset($item['shippingOptions'][0]['shippingCost']['value'])
            ? (float)$item['shippingOptions'][0]['shippingCost']['value'] : null;
        if ($shipCost !== null && $shipCost == 0.0) $freeShipCount++;

        // remise vendeur
        if (isset($item['marketingPrice']['discountPercentage'])) {
            $discountSum += (float)$item['marketingPrice']['discountPercentage'];
            $discountCount++;
        }

        // top rated
        if (!empty($item['topRatedBuyingExperience'])) $topRatedCount++;

        // condition breakdown
        $cond = $item['condition'] ?? 'Unknown';
        $conditions[$cond] = ($conditions[$cond] ?? 0) + 1;
    }

    arsort($conditions);
    $condBreakdown = [];
    foreach ($conditions as $cond => $cnt) {
        $condBreakdown[$cond] = $total > 0 ? round($cnt / $total * 100) : 0;
    }

    $pulse = [
        'watch_total'      => $watchTotal,
        'bid_total'        => $bidTotal,
        'free_ship_pct'    => $total > 0 ? round($freeShipCount / $total * 100) : 0,
        'avg_discount_pct' => $discountCount > 0 ? round($discountSum / $discountCount) : 0,
        'top_rated_count'  => $topRatedCount,
        'listings_count'   => $total,
        'condition_breakdown' => $condBreakdown,
        'cached_at'        => time(),
    ];

    @file_put_contents($cacheFile, json_encode($pulse));
    return $pulse;
}
