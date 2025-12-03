<?php
declare(strict_types=1);

// ----------------------------------------------------
// keywordurl.php
// Met à jour keywords.keywordURL à partir de keywords.keyword_name
// ----------------------------------------------------

/**
 * Chargements
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';

// Sécurité basique headers si exécuté en HTTP
if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
}

// ----------------------------------------------------
// Options: dry-run via CLI (--dry-run) ou HTTP (?dry=1)
// ----------------------------------------------------
$dryRun = false;
if (PHP_SAPI === 'cli') {
    $dryRun = in_array('--dry-run', $argv, true) || in_array('--dry', $argv, true);
} else {
    $dryRun = isset($_GET['dry']) && $_GET['dry'] == '1';
}

// ----------------------------------------------------
// Sélection des lignes à traiter
// - priorité: celles où keywordURL est NULL ou vide
// - sinon: tout (si ?all=1 ou --all)
// ----------------------------------------------------
$processAll = false;
if (PHP_SAPI === 'cli') {
    $processAll = in_array('--all', $argv, true);
} else {
    $processAll = isset($_GET['all']) && $_GET['all'] == '1';
}

$sqlSelect = $processAll
    ? "SELECT id, keyword_name, keywordURL FROM keywords WHERE keyword_name IS NOT NULL"
    : "SELECT id, keyword_name, keywordURL FROM keywords WHERE keyword_name IS NOT NULL AND (keywordURL IS NULL OR keywordURL = '')";

$stmt = $pdo->prepare($sqlSelect);
$stmt->execute();

$rows = $stmt->fetchAll();
$total = count($rows);

echo "Trouvé: {$total} enregistrement(s) à traiter" . ($processAll ? " (mode --all)" : "") . ($dryRun ? " [DRY-RUN]" : "") . "\n";


// ----------------------------------------------------
// Prépare l'UPDATE
// ----------------------------------------------------
$update = $pdo->prepare("UPDATE keywords SET keywordURL = :slug WHERE id = :id");

// ----------------------------------------------------
// Boucle de traitement
// ----------------------------------------------------
$updated = 0;
$skipped = 0;
$errors  = 0;

foreach ($rows as $r) {
    $id   = (int)$r['id'];
    $name = (string)$r['keyword_name'];
    $prev = (string)($r['keywordURL'] ?? '');

    $slug = clean_url($name);

    // Si le slug est vide (ex: nom vide ou que des caractères filtrés) → on saute
    if ($slug === '') {
        $skipped++;
        echo "[SKIP id={$id}] keyword_name vide/invalide\n";
        continue;
    }

    // Si identique → skip (sauf si --all demandé et tu veux forcer, ici on garde skip)
    if ($prev === $slug) {
        $skipped++;
        continue;
    }

    try {
        if ($dryRun) {
            echo "[DRY id={$id}] {$prev} => {$slug}\n";
        } else {
            $update->execute([':slug' => $slug, ':id' => $id]);
            $updated++;
            echo "[OK   id={$id}] {$prev} => {$slug}\n";
        }
    } catch (Throwable $e) {
        $errors++;
        fwrite(STDERR, "[ERR  id={$id}] " . $e->getMessage() . "\n");
    }
}

// ----------------------------------------------------
// Résumé
// ----------------------------------------------------
echo "----------------------------------------\n";
echo "Terminé. Updated: {$updated}, Skipped: {$skipped}, Errors: {$errors}" . ($dryRun ? " [DRY-RUN]" : "") . "\n";

/****
 * 
 * SELET 20 random keywords FOR Homepage.
 * 
 */

// Option: remettre tout à 0 avant de marquer les 20
$resetBefore = false;

// 1) S'assurer que la colonne homepage existe
$col = $pdo->query("SHOW COLUMNS FROM keywords LIKE 'homepage'");
if ($col->rowCount() === 0) {
    $pdo->exec("ALTER TABLE keywords ADD COLUMN homepage TINYINT(1) NOT NULL DEFAULT 0");
    // index (optionnel)
    $pdo->exec("CREATE INDEX idx_keywords_homepage ON keywords(homepage)");
}

// 2) Récupérer 20 IDs aléatoires
// NOTE: ORDER BY RAND() est OK si la table n'est pas énorme.
// Pour les très gros volumes, je peux te donner une variante scalable.
$sql = "SELECT id FROM keywords ORDER BY RAND() LIMIT 20";
$ids = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN, 0);

if (empty($ids)) {
    echo "[INFO] Aucun keyword trouvé.\n";
    exit;
}

$pdo->beginTransaction();
try {
    if ($resetBefore) {
        $pdo->exec("UPDATE keywords SET homepage = 0");
    }

    // 3) Mettre homepage=1 pour ces IDs
    $in = implode(',', array_fill(0, count($ids), '?'));
    $upd = $pdo->prepare("UPDATE keywords SET homepage = 1 WHERE id IN ($in)");
    $upd->execute($ids);

    $pdo->commit();
    echo "[OK] homepage=1 appliqué à ".count($ids)." keywords. IDs: ".implode(',', $ids).PHP_EOL;
    if ($resetBefore) echo "[OK] Les autres keywords ont été remis à 0.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log("Failed to update random homepage keywords: ".$e->getMessage());
    throw $e;
}