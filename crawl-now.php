<?php
/**
 * crawl-now.php — AJAX endpoint to trigger an on-demand eBay crawl for a keyword.
 * Called by inc/crawl-loading.php via fetch().
 * Returns JSON: { "done": true, "count": N }
 */
header('Content-Type: application/json; charset=utf-8');
set_time_limit(60);

$kid = (int)($_GET['kid'] ?? 0);
if ($kid <= 0) {
    echo json_encode(['error' => 'invalid_kid']);
    exit;
}

require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/functions.php';
require __DIR__ . '/scripts/crawler/ebay_browse_crawler.php';

// Verify keyword belongs to this site's DB
$stmt = $pdo->prepare("SELECT id FROM keywords WHERE id = :kid LIMIT 1");
$stmt->execute([':kid' => $kid]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'not_found']);
    exit;
}

// Re-activate so updateAds doesn't mark it inactive before trying
$pdo->prepare("UPDATE keywords SET active = 1 WHERE id = :kid")->execute([':kid' => $kid]);

// Crawl — buffer output so updateAds() echo statements don't corrupt the JSON response
ob_start();
updateAds($pdo, '', $_EBAY_MAX_ADS, $countryCode, null, $kid, "update", false);
ob_end_clean();

// Count products now in DB
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM ads WHERE keyword_id = :kid");
$stmt2->execute([':kid' => $kid]);
$count = (int)$stmt2->fetchColumn();

// If still nothing, deactivate again
if ($count === 0) {
    $pdo->prepare("UPDATE keywords SET active = 0 WHERE id = :kid")->execute([':kid' => $kid]);
}

echo json_encode(['done' => true, 'count' => $count]);
