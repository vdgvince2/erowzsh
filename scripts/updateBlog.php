<?php
declare(strict_types=1);



/*
CLI:
php updateBlog.php US used.forsale
=> $argv[1] = "US" (optionnel)
=> $argv[2] = "used.forsale" (obligatoire)
*/

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';

// -----------------------------------------------------------------------------
// CLI only
// -----------------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "ERROR: ce script doit être exécuté en CLI.\n");
    exit(1);
}


$country      = $argv[1] ?? null; // optionnel
$domainforFeed = $argv[2] ?? '';

if ($domainforFeed === '') {
    fwrite(STDERR, "ERROR: domaine manquant.\n");
    fwrite(STDERR, "Usage: php updateBlog.php <COUNTRY?> <DOMAIN>\n");
    fwrite(STDERR, "Ex   : php updateBlog.php US used.forsale\n");
    exit(1);
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    fwrite(STDERR, "ERROR: \$pdo n'est pas initialisé. Vérifie inc/config.php (connexion DB en CLI).\n");
    exit(1);
}

// IMPORTANT: activer les exceptions AVANT prepare/execute
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Optionnel mais utile:
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

echo "Update this domain: {$domainforFeed}\n";

// -----------------------------------------------------------------------------
// Fetch RSS XML
// -----------------------------------------------------------------------------
$feedUrl = 'https://www.' . $domainforFeed . '/mag/feed';
echo $feedUrl . PHP_EOL;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $feedUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_USERAGENT      => 'UsedMagFeedBot/1.0 (+https://www.' . $domainforFeed . ')',
    CURLOPT_HTTPHEADER     => [
        'Accept: application/rss+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.5'
    ],
]);

$response = curl_exec($ch);

if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    fwrite(STDERR, "ERROR fetching feed: {$error}\n");
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode < 200 || $httpCode >= 300) {
    fwrite(STDERR, "ERROR: HTTP status {$httpCode}\n");
    exit(1);
}

$xmlString = trim($response);

// -----------------------------------------------------------------------------
// Parse XML (RSS)
// -----------------------------------------------------------------------------
libxml_use_internal_errors(true);
$xml = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);

if ($xml === false) {
    $errs = array_map(fn($e) => trim($e->message), libxml_get_errors());
    libxml_clear_errors();
    fwrite(STDERR, "ERROR: impossible de parser le XML du feed.\n" . implode("\n", $errs) . "\n");
    exit(1);
}

if (!isset($xml->channel->item)) {
    fwrite(STDERR, "ERROR: pas d'items dans le feed.\n");
    exit(1);
}

// -----------------------------------------------------------------------------
// SQL (UPSERT propre: PAS de INSERT IGNORE)
// -----------------------------------------------------------------------------
// NOTE: ça ne fonctionnera comme "duplicate" QUE si guid est UNIQUE/PRIMARY KEY.
$upsertSql = "
    INSERT INTO mag_feed_articles
        (guid, title, link, creator, pub_date, category, description, photo)
    VALUES
        (:guid, :title, :link, :creator, :pub_date, :category, :description, :photo)
    ON DUPLICATE KEY UPDATE
        title       = VALUES(title),
        link        = VALUES(link),
        creator     = VALUES(creator),
        pub_date    = VALUES(pub_date),
        category    = VALUES(category),
        description = VALUES(description),
        photo       = VALUES(photo),
        updated_at  = CURRENT_TIMESTAMP
";
$upsertStmt = $pdo->prepare($upsertSql);

// Optionnel: vérifier que guid est bien unique (debug simple)
try {
    $hasGuidUnique = false;
    $idx = $pdo->query("SHOW INDEX FROM mag_feed_articles")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($idx as $row) {
        if (
            isset($row['Column_name'], $row['Non_unique'])
            && $row['Column_name'] === 'guid'
            && (int)$row['Non_unique'] === 0
        ) {
            $hasGuidUnique = true;
            break;
        }
    }
    if (!$hasGuidUnique) {
        fwrite(STDERR, "⚠️ WARNING: guid n'a pas l'air UNIQUE. ON DUPLICATE KEY ne déclenchera pas sans index unique.\n");
        fwrite(STDERR, "   -> Ajoute un UNIQUE INDEX sur guid.\n");
    }
} catch (Throwable $e) {
    // Si pas de droits sur SHOW INDEX, on ignore.
}

// -----------------------------------------------------------------------------
// Helpers debug
// -----------------------------------------------------------------------------
function dumpPdoError(PDOStatement $stmt): void {
    $err = $stmt->errorInfo();
    // $err[0] SQLSTATE, $err[1] driver code, $err[2] message
    fwrite(STDERR, "❌ PDO errorInfo:\n");
    fwrite(STDERR, "SQLSTATE: " . ($err[0] ?? '') . "\n");
    fwrite(STDERR, "Driver code: " . ($err[1] ?? '') . "\n");
    fwrite(STDERR, "Message: " . ($err[2] ?? '') . "\n");
}

// -----------------------------------------------------------------------------
// Loop items
// -----------------------------------------------------------------------------
$countInserted = 0;
$countUpdated  = 0;
$countNoop     = 0;
$countErrors   = 0;

foreach ($xml->channel->item as $item) {
    $guid = trim((string) $item->guid);
    if ($guid === '') {
        $guid = trim((string) $item->link); // fallback
    }
    if ($guid === '') {
        continue;
    }

    $title = trim((string) $item->title);
    $link  = trim((string) $item->link);

    // dc:creator
    $creator = '';
    $dc = $item->children('http://purl.org/dc/elements/1.1/');
    if (isset($dc->creator)) {
        $creator = trim((string) $dc->creator);
    }

    // catégories multiples -> string join
    $categories = [];
    if (isset($item->category)) {
        foreach ($item->category as $cat) {
            $c = trim((string) $cat);
            if ($c !== '') $categories[] = $c;
        }
    }
    $category = implode(' | ', array_values(array_unique($categories)));

    // content:encoded si dispo
    $ns = $item->getNamespaces(true);
    $contentEncoded = '';
    if (isset($ns['content'])) {
        $contentChildren = $item->children($ns['content']);
        $contentEncoded = trim((string) $contentChildren->encoded);
    }

    $description = trim((string) $item->description);
    $htmlContent = $contentEncoded !== '' ? $contentEncoded : $description;

    // extraire l'URL image depuis le 1er <figure> (puis <img src=...>)
    $imgUrl = '';
    if ($htmlContent !== '' && preg_match('~<figure\b[^>]*>(.*?)</figure>~is', $htmlContent, $figureMatch)) {
        if (preg_match('~<img[^>]+src=["\']([^"\']+)["\']~i', $figureMatch[1], $imgMatch)) {
            $imgUrl = $imgMatch[1];
        }
    }

    // pubDate -> DATETIME (ou NULL)
    $pubDate = null;
    $pubDateRaw = trim((string) $item->pubDate);
    if ($pubDateRaw !== '') {
        $ts = strtotime($pubDateRaw);
        if ($ts !== false) {
            $pubDate = date('Y-m-d H:i:s', $ts);
        }
    }

    try {
        $upsertStmt->execute([
            ':guid'        => $guid,
            ':title'       => $title,
            ':link'        => $link,
            ':creator'     => $creator,
            ':pub_date'    => $pubDate,
            ':category'    => $category,
            ':description' => $description,
            ':photo'       => $imgUrl
        ]);

        // Debug comportement:
        // - insert: souvent 1
        // - update: souvent 2 (mais ça dépend)
        // - noop (valeurs identiques): parfois 0
        $rc = $upsertStmt->rowCount();

        if ($rc === 1) {
            $countInserted++;
        } elseif ($rc === 2) {
            $countUpdated++;
        } else {
            $countNoop++;
        }
    } catch (PDOException $e) {
        $countErrors++;

        fwrite(STDERR, "\n❌ MySQL/PDO exception sur guid={$guid}\n");
        fwrite(STDERR, "Message : " . $e->getMessage() . "\n");
        fwrite(STDERR, "SQLSTATE : " . $e->getCode() . "\n");
        fwrite(STDERR, "SQL      : " . $upsertStmt->queryString . "\n");

        // Si tu veux encore plus de détails driver:
        dumpPdoError($upsertStmt);

        // Continuer sur l’item suivant au lieu de stopper tout le batch
        continue;
    }
}

echo "Done.\n";
echo "Inserted: {$countInserted}, Updated: {$countUpdated}, No-op: {$countNoop}, Errors: {$countErrors}\n";
exit($countErrors > 0 ? 2 : 0);
