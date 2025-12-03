<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';

/// -----------------------------------------------------------------------------
// Récupération du flux
// -----------------------------------------------------------------------------
$feedUrl = 'https://www.used.forsale/mag/wp-json/used/v1/feed';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $feedUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_USERAGENT      => 'UsedMagFeedBot/1.0',
]);

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    die('ERROR fetching feed: ' . $error);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode < 200 || $httpCode >= 300) {
    die('ERROR: HTTP status ' . $httpCode);
}

// -----------------------------------------------------------------------------
// L’endpoint renvoie du JSON qui contient une string XML échappée
// --------------------------------------------------
$decoded = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERROR: la réponse n'est pas du JSON valide : " . json_last_error_msg() . "\n");
}

if (is_string($decoded)) {
    // Cas le plus probable ici
    $xmlString = $decoded;
} elseif (is_array($decoded) && isset($decoded['feed'])) {
    // Variante si jamais le XML est dans une clé 'feed'
    $xmlString = $decoded['feed'];
} else {
    // Si ça plante, fais un var_dump($decoded) pour voir la structure réelle
    die("ERROR: structure JSON inattendue\n");
}

// Optionnel : debug
// file_put_contents(__DIR__ . '/debug_feed.xml', $xmlString);

// Nettoyage basique
$xmlString = trim($xmlString);

// -----------------------------------------------------------------------------
// Parsing XML
// -----------------------------------------------------------------------------
$xml = @simplexml_load_string($xmlString);
if ($xml === false) {
    die("ERROR: impossible de parser le XML du feed.\n");
}

// -----------------------------------------------------------------------------
// Préparation de la requête SQL (upsert sur guid)
// -----------------------------------------------------------------------------
$sql = "
    INSERT INTO mag_feed_articles
        (guid, title, link, creator, pub_date, category, description)
    VALUES
        (:guid, :title, :link, :creator, :pub_date, :category, :description)
    ON DUPLICATE KEY UPDATE
        title = VALUES(title),
        link = VALUES(link),
        creator = VALUES(creator),
        pub_date = VALUES(pub_date),
        category = VALUES(category),
        description = VALUES(description),
        updated_at = CURRENT_TIMESTAMP
";

$stmt = $pdo->prepare($sql);

// -----------------------------------------------------------------------------
// Parcours des <item> du flux
// -----------------------------------------------------------------------------
$countInserted = 0;
$countUpdated  = 0;

if (!isset($xml->channel->item)) {
    die("ERROR: pas d'items dans le feed.\n");
}

foreach ($xml->channel->item as $item) {
    $title       = trim((string) $item->title);
    $link        = trim((string) $item->link);
    $creator     = trim((string) $item->children('dc', true)->creator);
    $pubDateRaw  = trim((string) $item->pubDate);
    $category    = trim((string) $item->category);
    $guid        = trim((string) $item->guid);
    $description = trim((string) $item->description);

    // Conversion pubDate -> DATETIME MySQL
    $pubDate = null;
    if ($pubDateRaw !== '') {
        $dt = date_create($pubDateRaw);
        if ($dt !== false) {
            $pubDate = $dt->format('Y-m-d H:i:s');
        }
    }

    $stmt->execute([
        ':guid'        => $guid,
        ':title'       => $title,
        ':link'        => $link,
        ':creator'     => $creator,
        ':pub_date'    => $pubDate,
        ':category'    => $category,
        ':description' => $description,
    ]);

    // 1 = insert, 2 = update (ON DUPLICATE KEY)
    $rowCount = $stmt->rowCount();
    if ($rowCount === 1) {
        $countInserted++;
    } elseif ($rowCount === 2) {
        $countUpdated++;
    }
}

echo "Done. Inserted: {$countInserted}, Updated: {$countUpdated}\n";