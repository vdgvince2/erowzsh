<?php
/***
 * Normalisation des niveaux de catégories
 * 
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';



// --- paramètres ---
$BATCH_SIZE = 2000;          // nombre de lignes lues par batch
$FORCE_L1_FALLBACK = false;  // si true, impose un L1 quand vide
$FALLBACK_L1 = 'Uncategorized';


function normalizePath(?string $s): string {
    if ($s === null) return '';
    // Unifier les \r\n etc. (optionnel)
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    // Supprimer espaces autour des ':'
    $s = preg_replace('/\s*:\s*/u', ':', $s);
    // Compresser ':::' etc → ':'
    $s = preg_replace('/:{2,}/', ':', $s);
    // Trim ':' en tête/fin
    $s = trim($s, ": \t\n\r\0\x0B");
    // Trim final
    $s = trim($s);
    return $s;
}

// Split sûr: renvoie 3 niveaux max (strings vides si absents)
function splitLevels(string $norm): array {
    if ($norm === '') return ['', '', ''];
    $parts = explode(':', $norm, 3); // max 3 morceaux
    if (count($parts) < 3) {
        $parts = array_pad($parts, 3, '');
    }
    // Nettoyage léger des espaces
    foreach ($parts as &$p) {
        $p = trim($p);
    }
    return $parts; // [l1, l2, l3]
}

// Statements préparés
$selectStmt = $pdo->prepare("
    SELECT id, category_name_path
    FROM ads
    WHERE category_name_path IS NOT NULL
      -- Option: ne traiter que les lignes qui semblent incomplètes
      AND (category_level1 IS NULL OR category_level2 IS NULL OR category_level3 IS NULL)
      AND id > :after_id
    ORDER BY id
    LIMIT :lim
");

$updateStmt = $pdo->prepare("
    UPDATE ads
    SET category_level1 = :l1, category_level2 = :l2, category_level3 = :l3
    WHERE id = :id
");

$lastId = 0;
$totalRead = 0;
$totalUpdated = 0;

do {
    // keyset pagination (plus rapide que OFFSET)
    $selectStmt->bindValue(':after_id', $lastId, PDO::PARAM_INT);
    $selectStmt->bindValue(':lim', $BATCH_SIZE, PDO::PARAM_INT);
    $selectStmt->execute();
    $rows = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($rows);
    if ($count === 0) break;

    $pdo->beginTransaction();
    foreach ($rows as $r) {
        $id  = (int)$r['id'];
        $raw = $r['category_name_path'];

        $norm = normalizePath($raw);
        list($l1, $l2, $l3) = splitLevels($norm);

        if ($FORCE_L1_FALLBACK && $l1 === '') {
            $l1 = $FALLBACK_L1;
        }

        // Utilise NULLIF côté DB: si '', on envoie NULL
        $params = [
            ':l1' => ($l1 === '' ? null : $l1),
            ':l2' => ($l2 === '' ? null : $l2),
            ':l3' => ($l3 === '' ? null : $l3),
            ':id' => $id,
        ];
        // Pour la comparaison dans WHERE, on doit repasser les mêmes valeurs
        $params[':l1'] = $params[':l1']; // déjà correct
        $params[':l2'] = $params[':l2'];
        $params[':l3'] = $params[':l3'];

        $updateStmt->execute([':l1'=>$l1, ':l2'=>$l2, ':l3'=>$l3, ':id'=>$id]);
        
        $totalUpdated += $updateStmt->rowCount();

        $lastId = $id;
    }
    $pdo->commit();

    $totalRead += $count;
    echo "[INFO] Batch processed: read={$count}, totalRead={$totalRead}, totalUpdated={$totalUpdated}, lastId={$lastId}\n";

} while (true);

echo "[DONE] totalRead={$totalRead}, totalUpdated={$totalUpdated}\n";