<?php

/**
 * build_categories.php
 * - Agrège category_level1..3 depuis ads
 * - Déduplique sur name (UNIQUE)
 * - Renseigne level et url (slug)
 * - Renseigne parentid: level2 -> parent=level1 ; level3 -> parent=level2
 */

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';


/* 1) UPSERT des catégories (name, level, url) */
echo "***Start the query (takes 3min)".PHP_EOL;
$sqlCats = <<<SQL
SELECT name, MIN(level) AS level
FROM (
    /* L1 */
    SELECT TRIM(category_level1) AS name, 1 AS level, COUNT(*) AS cnt
    FROM ads
    WHERE category_level1 IS NOT NULL AND category_level1 <> ''
    GROUP BY TRIM(category_level1)

    UNION ALL

    /* L2 */
    SELECT TRIM(category_level2) AS name, 2 AS level, COUNT(*) AS cnt
    FROM ads
    WHERE category_level2 IS NOT NULL AND category_level2 <> ''
    GROUP BY TRIM(category_level2)

    UNION ALL

    /* L3 */
    SELECT TRIM(category_level3) AS name, 3 AS level, COUNT(*) AS cnt
    FROM ads
    WHERE category_level3 IS NOT NULL AND category_level3 <> ''
    GROUP BY TRIM(category_level3)
) t
WHERE name <> '' AND cnt >= 50
GROUP BY name
ORDER BY level, name
SQL;

$cats = $pdo->query($sqlCats)->fetchAll();
if (!$cats) {
    echo "Aucune catégorie trouvée.\n";
    exit(0);
}

$upsert = $pdo->prepare("
    INSERT INTO categories (name, level, url)
    VALUES (:name, :level, :url)
    ON DUPLICATE KEY UPDATE
        level = LEAST(level, VALUES(level)),
        url   = VALUES(url)
");

$pdo->beginTransaction();
$ins = $upd = 0;

try {
    foreach ($cats as $c) {
        $name  = (string)$c['name'];
        $level = (int)$c['level'];
        $slug  = clean_url($name);
        if ($slug === '') continue;

        $upsert->execute([
            ':name'  => $name,
            ':level' => $level,
            ':url'   => $slug,
        ]);

        $rc = $upsert->rowCount();
        if ($rc === 1) $ins++;      // insert
        elseif ($rc === 2) $upd++;  // update via ON DUPLICATE
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

echo "Catégories: inserted={$ins}, updated={$upd}\n";

/* 2) Map name -> id pour fixer les parents */
$map = [];
$rows = $pdo->query("SELECT id, name FROM categories")->fetchAll();
foreach ($rows as $r) {
    $map[$r['name']] = (int)$r['id'];
}

/* 3) Relations enfant → parent depuis ads */
echo "*** Start the mapping of levels".PHP_EOL;
$sqlRel = <<<SQL
SELECT DISTINCT child, parent FROM (
    SELECT TRIM(category_level2) AS child, TRIM(category_level1) AS parent
    FROM ads
    WHERE category_level2 IS NOT NULL AND category_level2 <> ''
      AND category_level1 IS NOT NULL AND category_level1 <> ''
    UNION
    SELECT TRIM(category_level3) AS child, TRIM(category_level2) AS parent
    FROM ads
    WHERE category_level3 IS NOT NULL AND category_level3 <> ''
      AND category_level2 IS NOT NULL AND category_level2 <> ''
) r
WHERE child <> '' AND parent <> ''
SQL;

$rels = $pdo->query($sqlRel)->fetchAll();

$updParent = $pdo->prepare("
    UPDATE categories
    SET parentid = :pid_set
    WHERE id = :cid
      AND (parentid IS NULL OR parentid <> :pid_check)
");


$set = $missChild = $missParent = 0;
foreach ($rels as $rel) {
    $childName  = $rel['child'];
    $parentName = $rel['parent'];

    if (!isset($map[$childName]))  { $missChild++;  continue; }
    if (!isset($map[$parentName])) { $missParent++; continue; }

    $cid = $map[$childName];
    $pid = $map[$parentName];

    if ($cid === $pid) continue; // parano: évite parent=soi-même

    $updParent->execute([
        ':pid_set'   => $pid,
        ':pid_check' => $pid,
        ':cid'       => $cid,
    ]);
    if ($updParent->rowCount() > 0) $set++;
}

echo "Parents: set={$set}, missing_child={$missChild}, missing_parent={$missParent}\n";

/* 4) (Optionnel) Nettoyage: s'assurer que level=1 a parentid NULL */
$fixRoot = $pdo->exec("UPDATE categories SET parentid = NULL WHERE level = 1 AND parentid IS NOT NULL");
if ($fixRoot !== false) {
    echo "Roots fixed (set parentid=NULL for level=1): {$fixRoot}\n";
}


/* 5) Build full slug_path from ancestors (level1/2/3) */
$rows = $pdo->query("SELECT id, parentid, url FROM categories")->fetchAll(PDO::FETCH_ASSOC);

$byId = [];
foreach ($rows as $r) {
    $byId[(int)$r['id']] = [
        'parentid' => isset($r['parentid']) ? (int)$r['parentid'] : null,
        'url'      => (string)$r['url'],
    ];
}

$cache = [];
$building = [];

/**
 * Returns full path starting with '/', e.g. '/collectables/transportation-collectables'
 */
$buildPath = function (int $id) use (&$buildPath, &$byId, &$cache, &$building): string {
    if (isset($cache[$id])) return $cache[$id];

    // cycle guard (defensif)
    if (isset($building[$id])) {
        return $cache[$id] = '/' . ($byId[$id]['url'] ?? '');
    }
    $building[$id] = true;

    $node = $byId[$id] ?? null;
    if (!$node) {
        unset($building[$id]);
        return $cache[$id] = '/';
    }

    $url = trim((string)$node['url']);
    $pid = $node['parentid'] ?: null;

    if ($pid && isset($byId[$pid])) {
        $parentPath = $buildPath($pid);
        // normalise les slashes
        $parentPath = rtrim($parentPath, '/');
        $full = $parentPath . '/' . $url;
    } else {
        $full = '/' . $url; // level 1
    }

    $full = preg_replace('#/{2,}#', '/', $full);         // évite doubles slashes
    $full = preg_replace('#(^/)?(.+)$#', '/$2', $full);  // force un seul slash leading
    $cache[$id] = $full;
    unset($building[$id]);
    return $full;
};

$upd = $pdo->prepare("
    UPDATE categories
    SET slug_path = :p_set
    WHERE id = :id
      AND COALESCE(slug_path, '') <> :p_cmp
");

$updated = 0;
foreach (array_keys($byId) as $id) {
    $path = $buildPath((int)$id);
    // (optionnel) couper à 255 pour éviter l'erreur de longueur
    if (strlen($path) > 255) $path = substr($path, 0, 255);

    $upd->execute([
        ':p_set' => $path,
        ':p_cmp' => $path,
        ':id'    => $id,
    ]);
    $updated += $upd->rowCount();
}

echo "Slug paths updated={$updated}\n";
