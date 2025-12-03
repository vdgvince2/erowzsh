<?php
/**
 * Import CSV -> MySQL avec mysqli (sans PDO)
 * PHP >= 8.0 recommandé.
 *
 * Ex:
 * php import_csv_mysqli.php --file=/path/file.csv --host=127.0.0.1 --port=8889 --db=sh --user=sh --pass=test --table=interne_tous
 */

$options = getopt("", [
    "file:", "host:", "db:", "user:", "pass:",
    "table::", "charset::", "sample::", "batch::",
    "port::", "socket::"
]);

foreach (["file","host","db","user","pass"] as $r) {
    if (!isset($options[$r])) {
        fwrite(STDERR, "Missing --$r\n");
        exit(1);
    }
}

$csvPath = $options["file"];
$host    = $options["host"];
$db      = $options["db"];
$user    = $options["user"];
$pass    = $options["pass"];
$table   = $options["table"]   ?? null;
$charset = $options["charset"] ?? "utf8mb4";
$sampleN = (int)($options["sample"] ?? 500);
$batchSz = (int)($options["batch"]  ?? 1000);
$port    = (int)($options["port"]   ?? ini_get("mysqli.default_port") ?: 3306);
$socket  = $options["socket"]       ?? ini_get("mysqli.default_socket") ?: null;

if (!is_readable($csvPath)) {
    fwrite(STDERR, "CSV not readable: $csvPath\n");
    exit(1);
}

/** Détecter séparateur sur un chunk du fichier */
function detectDelimiter(string $path): string {
    $delims = [",",";","\t","|"];
    $fh = fopen($path, "rb");
    if (!$fh) return ",";
    $chunk = "";
    $max = 100000;
    while (!feof($fh) && strlen($chunk) < $max) { $chunk .= fread($fh, 8192); }
    fclose($fh);

    $scores = [];
    $lines = preg_split("/\r\n|\n|\r/", $chunk);
    foreach ($delims as $d) {
        $counts = [];
        foreach (array_slice($lines, 0, 20) as $l) {
            if ($l === "") continue;
            $counts[] = substr_count($l, $d);
        }
        if (!$counts) { $scores[$d] = 0; continue; }
        $avg = array_sum($counts)/count($counts);
        $var = 0.0;
        foreach ($counts as $c) $var += ($c - $avg) * ($c - $avg);
        $var /= max(count($counts),1);
        $scores[$d] = $avg - 0.1*$var;
    }
    arsort($scores);
    return key($scores) ?: ",";
}

/** Nettoyage / unicité noms colonnes */
function sanitizeHeaders(array $headers): array {
    $out = [];
    $seen = [];
    foreach ($headers as $i => $h) {
        if ($i===0) {
            // remove BOM
            $h = preg_replace('/^\xEF\xBB\xBF/', '', (string)$h);
        }
        $h = trim((string)$h);
        if ($h === "") $h = "col_" . ($i+1);

        // normalize
        $h = mb_strtolower($h);
        $h = preg_replace('/[^a-z0-9_]+/u', '_', $h);
        $h = preg_replace('/_+/', '_', $h);
        $h = trim($h, "_");
        if ($h === "") $h = "col_" . ($i+1);

        // tronquer à 55 chars pour laisser place à suffixe
        $max = 55;
        if (strlen($h) > $max) {
            $h = substr($h, 0, $max);
        }

        // unicité
        $base = $h;
        $k = 1;
        while (isset($seen[$h])) {
            $h = substr($base, 0, $max) . "_" . $k;
            $k++;
        }
        $seen[$h] = true;
        $out[] = $h;
    }
    return $out;
}


/** Inférence de types pour CREATE TABLE */
function inferTypes(array $headers, SplFileObject $csv, string $delimiter, int $sampleN): array {
    $n = count($headers);
    $types = array_fill(0, $n, [
        "type"=>"VARCHAR", "maxLen"=>0, "decimals"=>0,
        "isInt"=>true, "isBigInt"=>false, "isNumeric"=>true,
        "isDate"=>true, "isDateTime"=>true, "isBool"=>true
    ]);

    $csv->rewind();
    $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
    $csv->setCsvControl($delimiter, '"', '\\');

    // skip header
    $csv->current(); $csv->next();

    $count=0;
    while(!$csv->eof() && $count < $sampleN){
        $row = $csv->current(); $csv->next();
        if (!is_array($row) || (count($row)===1 && $row[0]===null)) continue;
        if (count($row) < $n) $row = array_pad($row, $n, null);
        if (count($row) > $n) $row = array_slice($row, 0, $n);

        foreach ($row as $i=>$val){
            if ($val===null) continue;
            $val = (string)$val;
            $valTrim = trim($val);
            $len = strlen($val);
            if ($len > $types[$i]["maxLen"]) $types[$i]["maxLen"] = $len;

            if ($valTrim==="" || strcasecmp($valTrim,"NULL")===0) continue;

            if (!preg_match('/^(?:0|1|true|false)$/i', $valTrim)) $types[$i]["isBool"]=false;

            if (preg_match('/^[+-]?\d+$/', $valTrim)) {
                if (strlen(ltrim($valTrim, '+-')) > 10) $types[$i]["isBigInt"]=true;
            } else { $types[$i]["isInt"]=false; }

            if (preg_match('/^[+-]?\d+(?:[.,]\d+)?$/', $valTrim)) {
                $norm = str_replace(',', '.', $valTrim);
                $parts = explode('.', $norm);
                $dec = isset($parts[1]) ? strlen(rtrim($parts[1],'0')) : 0;
                if ($dec > $types[$i]["decimals"]) $types[$i]["decimals"] = $dec;
            } else { $types[$i]["isNumeric"]=false; }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valTrim)) $types[$i]["isDate"]=false;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $valTrim)) $types[$i]["isDateTime"]=false;
        }
        $count++;
    }

    $final=[];
    foreach ($types as $i=>$t){
        if ($t["isBool"])               $final[$i] = "TINYINT(1) NULL";
        elseif ($t["isInt"] && !$t["isBigInt"]) $final[$i] = "INT NULL";
        elseif ($t["isInt"] &&  $t["isBigInt"]) $final[$i] = "BIGINT NULL";
        elseif ($t["isNumeric"]) {
            $scale = max(0, min(10, $t["decimals"]));
            $precision = max(12, $scale+10);
            $final[$i] = "DECIMAL($precision,$scale) NULL";
        }
        elseif ($t["isDateTime"])       $final[$i] = "DATETIME NULL";
        elseif ($t["isDate"])           $final[$i] = "DATE NULL";
        else {
            if ($t["maxLen"] <= 255)    $final[$i] = "VARCHAR(".max(1,min(255,$t["maxLen"]+10)).") NULL";
            elseif ($t["maxLen"] <= 65535)     $final[$i] = "TEXT NULL";
            elseif ($t["maxLen"] <= 16777215)  $final[$i] = "MEDIUMTEXT NULL";
            else                                $final[$i] = "LONGTEXT NULL";
        }
    }
    return $final;
}

function buildCreateTableSQL(string $table, array $headers, array $colTypes, string $charset): string {
    $cols=[];
    foreach ($headers as $i=>$h){
        $cols[] = sprintf("`%s` %s", $h, $colTypes[$i] ?? "TEXT NULL");
    }
    return "CREATE TABLE IF NOT EXISTS `{$table}` (\n  ".implode(",\n  ", $cols)."\n) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$charset}_unicode_ci;";
}

function buildInsertSQL(string $table, array $headers): string {
    $cols = array_map(fn($h)=>"`$h`", $headers);
    $phs  = array_fill(0, count($headers), "?");
    return "INSERT INTO `{$table}` (".implode(",", $cols).") VALUES (".implode(",", $phs).")";
}

/** Connexion mysqli */
$mysqli = mysqli_init();
if ($socket) {
    $ok = $mysqli->real_connect(null, $user, $pass, $db, null, $socket);
} else {
    $ok = $mysqli->real_connect($host, $user, $pass, $db, $port, null, MYSQLI_CLIENT_MULTI_RESULTS);
}
if (!$ok) {
    fwrite(STDERR, "mysqli connect error: ".$mysqli->connect_error."\n");
    exit(1);
}
if (!$mysqli->set_charset($charset)) {
    fwrite(STDERR, "Failed to set charset $charset: ".$mysqli->error."\n");
    exit(1);
}

/** Ouvrir CSV */
//$delimiter = detectDelimiter($csvPath);
$delimiter = ",";
$csv = new SplFileObject($csvPath, "rb");
$csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
$csv->setCsvControl($delimiter, '"', '\\');

/** Entêtes */
$headers = $csv->current();
if (!is_array($headers)) { fwrite(STDERR,"Impossible de lire l'entête CSV.\n"); exit(1); }
$headers = sanitizeHeaders($headers);
$colCount = count($headers);

/** Nom de table si absent */
if (!$table) {
    $base = pathinfo($csvPath, PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9_]+/', '_', $base) ?: "import_".date("Ymd_His");
    $table = strtolower($base);
}

/** Inférer types + créer table */
$types = inferTypes($headers, $csv, $delimiter, $sampleN);
$createSQL = buildCreateTableSQL($table, $headers, $types, $charset);
if (!$mysqli->query($createSQL)) {
    fwrite(STDERR, "CREATE TABLE error: ".$mysqli->error."\n");
    exit(1);
}

function isDecimalType(string $sqlType): bool {
    return stripos($sqlType, 'decimal') === 0 || stripos($sqlType, 'float') === 0 || stripos($sqlType, 'double') === 0;
}
function isIntType(string $sqlType): bool {
    return preg_match('/^(?:tinyint|smallint|int|bigint)\b/i', $sqlType) === 1;
}

/** Normalise un nombre en notation EU -> US (retourne string normalisée ou null si vide/Non-numérique) */
function normalizeEUFloat(?string $s): ?string {
    if ($s === null) return null;
    $s = trim($s);
    if ($s === '' || strcasecmp($s, 'NULL') === 0) return null;

    // vire unités & symboles fréquents (%, mg, g/L, etc.) en fin/fin de champ
    // (soft: on garde chiffres, signes, points, virgules, espaces)
    $s = preg_replace('/[^\d.,+\-\s]/u', '', $s);

    // supprime espaces (y compris espaces fines)
    $s = preg_replace('/[\h  ]/u', '', $s); // \h=horizontal space + nbsp ( ) + fine ( )

    $hasComma = str_contains($s, ',');
    $hasDot   = str_contains($s, '.');

    if ($hasComma && $hasDot) {
        // Probable format "1.234,56" (EU) -> enlever les points (séparateurs milliers), virgule -> point
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } elseif ($hasComma) {
        // "3,130" -> "3.130"
        $s = str_replace(',', '.', $s);
    } else {
        // "1234.56" -> OK
    }

    // Nettoie signes multiples, points en début/fin
    // Si ce n'est plus un nombre valide, on renvoie tel quel (laisse MySQL décider) :
    if (!preg_match('/^[+\-]?\d+(?:\.\d+)?$/', $s)) {
        return $s; // ex: "..." => laisser l'INSERT échouer si c'est vraiment pas un nombre
    }
    return $s;
}

// $types est retourné par inferTypes(...) et contient les SQL types choisis (ex: "DECIMAL(12,2) NULL")
$colSqlTypes = array_values($types);  // ex: ["INT NULL", "DECIMAL(12,3) NULL", "VARCHAR(255) NULL", ...]


/** Préparer INSERT */
$insertSQL = buildInsertSQL($table, $headers);
$stmt = $mysqli->prepare($insertSQL);
if (!$stmt) { fwrite(STDERR, "Prepare error: ".$mysqli->error."\n"); exit(1); }

/** Helper bind_param dynamique (tout en string, MySQL convertit) */
function bindParamsAsStrings(mysqli_stmt $stmt, array &$vals): bool {
    $types = str_repeat('s', count($vals));
    // bind_param attend des références
    $refs = [];
    $refs[] = &$types;
    foreach ($vals as $k => &$v) { $refs[] = &$v; }
    return $stmt->bind_param(...$refs);
}

/** Import */
$mysqli->autocommit(false);
$inserted = 0;

$csv->rewind();
$csv->setFlags(SplFileObject::READ_CSV);
$csv->setCsvControl($delimiter, '"', '\\');

// skip header
$csv->current(); $csv->next();

while(!$csv->eof()){
    $row = $csv->current(); $csv->next();
    if (!is_array($row)) continue;

    if (count($row) < $colCount)      $row = array_pad($row, $colCount, null);
    elseif (count($row) > $colCount)  $row = array_slice($row, 0, $colCount);

    // normalisation valeurs
    foreach ($row as $i => $v) {
        if ($v === null) { $row[$i] = null; continue; }
        $vv = (string)$v;
        $vv = str_replace("\r\n", "\n", $vv);
        $vv = preg_replace("/^\s+|\s+$/u", "", $vv);
        $row[$i] = ($vv === "") ? null : $vv;
    }

    // convertir null -> NULL SQL via bind: passer NULL natif avec ->bind_param('s', $val) donne '' (vide)
    // Contournement: on force chaîne pour non-nulls, et pour nulls on met NULL via EXECUTE après set_null
    // Plus simple: remplace null par empty string et laisse MySQL CAST ? Non, on veut NULL réels.
    // On va binder tout en string, mais pour NULL on utilisera mysqli_stmt::send_long_data trick ? Pas propre.
    // Option pragmatique: on reconstruit une requête avec literals échappés + NULL. (sécurisé car fichier local)
    // ==> On préfère rester en prepared et remplacer null par NULL avec "SET @a=?, ..." compliqué.
    // Choix pragmatique: build SQL échappé ici.

    $vals = [];
    foreach ($row as $i => $v) {
        $sqlType = $colSqlTypes[$i] ?? 'VARCHAR(255) NULL';

        if ($v === null) {
            $vals[] = "NULL";
            continue;
        }

        $vv = (string)$v;

        if (isIntType($sqlType)) {
            // vire espaces fines et séparateurs milliers EU
            $n = preg_replace('/[^\d+\-]/u', '', str_replace([' ', ' ', "\u{00A0}"], '', $vv));
            // si vide après nettoyage -> NULL
            if ($n === '' || !preg_match('/^[+\-]?\d+$/', $n)) {
                $vals[] = "NULL";
            } else {
                $vals[] = $n; // pas de quotes pour numériques
            }
        } elseif (isDecimalType($sqlType)) {
            $n = normalizeEUFloat($vv);
            if ($n === null || !preg_match('/^[+\-]?\d+(?:\.\d+)?$/', $n)) {
                $vals[] = "NULL";
            } else {
                $vals[] = $n; // pas de quotes pour DECIMAL
            }
        } else {
            // texte
            $vals[] = "'".$mysqli->real_escape_string($vv)."'";
        }
    }

    $sql = "INSERT INTO `{$table}` (`".implode("`,`",$headers)."`) VALUES (".implode(",", $vals).")";
    if (!$mysqli->query($sql)) {
        fwrite(STDERR, "Insert error (line ~{$inserted}): ".$mysqli->error."\n");
        continue;
    }


    $inserted++;
    if ($inserted % $batchSz === 0) {
        if (!$mysqli->commit()) { fwrite(STDERR, "Commit error: ".$mysqli->error."\n"); }
    }
}
$mysqli->commit();
$mysqli->close();

fwrite(STDOUT, "Import terminé: {$inserted} lignes insérées dans `{$table}` (séparateur: ".($delimiter === "\t" ? "\\t" : $delimiter).").\n");
