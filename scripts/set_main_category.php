<?php

/**
 * set_main_category_batch.php
 *
 * Parcourt TOUTE la table keywords et met à jour keywords.main_category
 * en choisissant la catégorie la plus profonde majoritaire trouvée dans ads.
 *
 * Usage:
 *   php set_main_category_batch.php
 *   php set_main_category_batch.php --dry-run
 *   php set_main_category_batch.php --limit=500 --offset=0
 */

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';


// --- Options CLI ---
$isCli  = (PHP_SAPI === 'cli');
$dryRun = false;
$limit  = null;   // exécution partielle possible
$offset = 0;

if ($isCli) {
    foreach ($argv as $arg) {
        if ($arg === '--dry' || $arg === '--dry-run') $dryRun = true;
        if (str_starts_with($arg, '--limit='))  $limit  = max(1, (int)substr($arg, 8));
        if (str_starts_with($arg, '--offset=')) $offset = max(0, (int)substr($arg, 9));
    }
} else {
    header('Content-Type: text/plain; charset=utf-8');
    $dryRun = isset($_GET['dry']) && $_GET['dry'] == '1';
    $limit  = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : null;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
}

// --- Précharger un map categories name->id & url->id pour accélérer ---
echo "**** Start setting main category to keywords *** \n";

$catNameToId = [];
$catUrlToId  = [];
foreach ($pdo->query("SELECT id, name, url FROM categories") as $row) {
    $catNameToId[$row['name']] = (int)$row['id'];
    if (!empty($row['url'])) $catUrlToId[$row['url']] = (int)$row['id'];
}

// --- Préparer statements réutilisables ---
$sqlPickCat = <<<SQL
SELECT name, depth, cnt
FROM (
  SELECT TRIM(category_level3) AS name, 3 AS depth, COUNT(*) AS cnt
    FROM ads WHERE keyword_id = :kwid3 AND category_level3 IS NOT NULL AND category_level3 <> ''
  GROUP BY name
  UNION ALL
  SELECT TRIM(category_level2) AS name, 2 AS depth, COUNT(*) AS cnt
    FROM ads WHERE keyword_id = :kwid2 AND category_level2 IS NOT NULL AND category_level2 <> ''
  GROUP BY name
  UNION ALL
  SELECT TRIM(category_level1) AS name, 1 AS depth, COUNT(*) AS cnt
    FROM ads WHERE keyword_id = :kwid1 AND category_level1 IS NOT NULL AND category_level1 <> ''
  GROUP BY name
) t
WHERE name <> ''
ORDER BY depth DESC, cnt DESC
LIMIT 1
SQL;
$stPickCat = $pdo->prepare($sqlPickCat);

$stUpdKw = $pdo->prepare("UPDATE keywords SET main_category = :cid WHERE id = :kid");

// --- Charger la liste des keywords (entière ou paginée) ---
$sqlKw = "SELECT id, keyword_name, main_category FROM keywords ORDER BY id ASC";
if ($limit !== null) {
    $sqlKw .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
}
$stKw = $pdo->query($sqlKw);

// --- Compteurs & exécution ---
$total = $set = $skipNoAds = $skipNoCat = $unchanged = 0;
$batchSize = 500;
$inTx = false;

while ($kw = $stKw->fetch()) {
    $total++;
    $kwId   = (int)$kw['id'];
    $kwName = (string)$kw['keyword_name'];

    // 1+2+3) Choisir la catégorie la plus profonde majoritaire
    $stPickCat->execute([':kwid1' => $kwId, ':kwid2' => $kwId, ':kwid3' => $kwId]);
    $chosen = $stPickCat->fetch();

    if (!$chosen) {
        $skipNoAds++;
        if ($total % 50 === 1) {
            echo "[NOADS] keyword #{$kwId} '{$kwName}' — aucune catégorie trouvée dans ads\n";
        }
        continue;
    }

    $catName = $chosen['name'];
    // $depth = (int)$chosen['depth']; // utile si tu veux le log

    // 4) Résoudre l'ID catégorie dans categories (name d'abord, sinon slug/url)
    $catId = $catNameToId[$catName] ?? null;
    if ($catId === null) {
        $slug  = clean_url($catName);
        $catId = $catUrlToId[$slug] ?? null;
        if ($catId === null) {
            $skipNoCat++;
            echo "[NOCAT] keyword #{$kwId} '{$kwName}' => '{$catName}' introuvable dans categories (name/url)\n";
            continue;
        }
    }

    // 5) UPDATE keywords.main_category
    if ($dryRun) {
        echo "[DRY] keyword #{$kwId} '{$kwName}' => main_category={$catId} ({$catName})\n";
        continue;
    }

    // Batch transaction simple (améliore la perf si beaucoup de rows)
    if (!$inTx) {
        $pdo->beginTransaction();
        $inTx = true;
    }

    $stUpdKw->execute([':cid' => $catId, ':kid' => $kwId]);
    if ($stUpdKw->rowCount() > 0) {
        $set++;
    } else {
        $unchanged++;
    }

    if (($set + $unchanged) % $batchSize === 0) {
        $pdo->commit();
        $inTx = false;
        echo "[COMMIT] progress: processed={$total}, set={$set}, unchanged={$unchanged}, noads={$skipNoAds}, nocat={$skipNoCat}\n";
    }
}

// Commit final si ouvert
if ($inTx) {
    $pdo->commit();
    $inTx = false;
}

// Résumé
echo "-----------------------------------------\n";
echo "Processed : {$total}\n";
echo "Updated   : {$set}\n";
echo "Unchanged : {$unchanged}\n";
echo "No ads    : {$skipNoAds}\n";
echo "No cat    : {$skipNoCat}\n";
echo $dryRun ? "[DRY-RUN]\n" : "";


/*** 
 * CHOOSE THE CATEGORIES FOR THE HOMEPAGE
 * 30 MAX, BASED ON THE ONES WITH MOST KEYWORDS INSIDE.
 */

echo "**** Start attributing categories to homepage *** \n";

// ---- Option : remettre toutes les catégories à 0 avant de marquer le Top 30
$resetOthers = true; // passe à true si tu veux un reset global

$pdo->beginTransaction();
try {
    // 1) Récup Top 30 catégories par nb de keywords
    $sqlTop30 = "
        SELECT c.id
        FROM keywords k
        JOIN categories c ON c.id = k.main_category
        WHERE c.level = 1
        GROUP BY c.id
        ORDER BY COUNT(*) DESC
        LIMIT 30
    ";
    $stmt = $pdo->query($sqlTop30);
    $topIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // array d'IDs

    if ($resetOthers) {
        // 2a) (optionnel) reset global
        $pdo->exec("UPDATE categories SET homepage = 0");
    }

    if (!empty($topIds)) {
        // 2b) Mettre homepage=1 pour ces IDs
        $placeholders = implode(',', array_fill(0, count($topIds), '?'));
        $upd = $pdo->prepare("UPDATE categories SET homepage = 1 WHERE id IN ($placeholders)");
        $upd->execute($topIds);
    }

    $pdo->commit();

    // Log minimal
    echo "[OK] Top IDs: " . implode(',', $topIds) . PHP_EOL;
    echo "[OK] homepage=1 marqué pour " . count($topIds) . " catégories." . PHP_EOL;
    if ($resetOthers) {
        echo "[OK] Les autres catégories ont été remises à 0." . PHP_EOL;
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    // Log explicite
    error_log("Homepage update failed: " . $e->getMessage());
    throw $e;
}



/*****
 * Faire un script : 500 produits sans catégorie. mettre à jour via une query LIKE/FTS sur ads. 
 * Essayer d'assigner rapidement
 * Si pas, fallback > catégories autre. 
 *  */

echo "**** Start adding category to unclassifieds keywords *** \n";

// 0) Récupérer la catégorie par défaut (url='ebay')
$defaultCatId = (function(PDO $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE url = ? LIMIT 1");
    $stmt->execute(['ebay']);
    $id = $stmt->fetchColumn();
    if (!$id) {
        throw new RuntimeException("Default category not found (categories.url='ebay'). Create it first.");
    }
    return (int)$id;
})($pdo);

// 1) Préparer les statements
$selKeywords = $pdo->prepare("
    SELECT id
    FROM keywords
    WHERE main_category IS NULL OR main_category = 0
    LIMIT 100000
");

$selFirstAdL1 = $pdo->prepare("
    SELECT category_level1
    FROM ads
    WHERE keyword_id = ?
      AND category_level1 IS NOT NULL AND category_level1 <> ''
    ORDER BY id ASC
    LIMIT 1
");

$selCategoryByName = $pdo->prepare("
    SELECT id
    FROM categories
    WHERE TRIM(LOWER(name)) = ?
    LIMIT 1
");

$updKeyword = $pdo->prepare("
    UPDATE keywords
    SET main_category = :cat_id
    WHERE id = :kw_id
");

// 2) Helpers
$normalize = function(?string $s): string {
    if ($s === null) return '';
    $s = trim($s);
    // normalisation simple; garde les accents si ta BDD est en utf8mb4_ci (insensible à la casse)
    // Sinon, décommente pour translittérer :
    // $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    return mb_strtolower($s, 'UTF-8');
};

// Cache pour éviter des lookups répétés
$catNameToId = [];
$catNameToId['__DEFAULT__'] = $defaultCatId;

// 3) Exécution
$pdo->beginTransaction();

$processed = 0;
$assignedFromAd = 0;
$assignedDefault = 0;
$noAdFound = 0;
$noCategoryMatch = 0;

$selKeywords->execute();
while ($kw = $selKeywords->fetch(PDO::FETCH_ASSOC)) {
    $kwId = (int)$kw['id'];

    // a) chercher la première annonce liée
    $selFirstAdL1->execute([$kwId]);
    $catL1 = $selFirstAdL1->fetchColumn();

    $targetCatId = null;

    if ($catL1 && $catL1 !== '') {
        $normName = $normalize($catL1);

        if (isset($catNameToId[$normName])) {
            $targetCatId = $catNameToId[$normName];
        } else {
            $selCategoryByName->execute([$normName]);
            $cid = $selCategoryByName->fetchColumn();
            if ($cid) {
                $targetCatId = (int)$cid;
                $catNameToId[$normName] = $targetCatId;
            } else {
                // pas de correspondance dans categories → fallback
                $noCategoryMatch++;
            }
        }
    } else {
        $noAdFound++;
    }

    if (!$targetCatId) {
        $targetCatId = $defaultCatId;
        $assignedDefault++;
    } else {
        $assignedFromAd++;
    }

    // b) update keyword
    $updKeyword->execute([
        ':cat_id' => $targetCatId,
        ':kw_id'  => $kwId,
    ]);

    $processed++;
    if (($processed % 1000) === 0) {
        // petit flush transactionnel toutes les 1000 MàJ si tu veux éviter un gros verrou
        $pdo->commit();
        $pdo->beginTransaction();
    }
}

$pdo->commit();

// 4) Logs
echo "[DONE] Processed keywords: {$processed}\n";
echo "[INFO] Assigned from ads L1: {$assignedFromAd}\n";
echo "[INFO] Assigned default: {$assignedDefault}\n";
echo "[WARN] No Ad found: {$noAdFound}\n";
echo "[WARN] No Category match (on L1): {$noCategoryMatch}\n";