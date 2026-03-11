<?php
/**
 * /inc/keywords_stats.php
 * JSON stats for table `keywords` on fields: last_update, last_visited
 *
 * Usage:
 *   /inc/keywords_stats.php?token=XXXX
 */

declare(strict_types=1);

require __DIR__ . '/config.php'; 
require __DIR__ . '/functions.php'; 

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const API_TOKEN = 'FJKWrRptk7vOEv4jxuxvWQqJif26RIHN'; // <-- à définir (unique et long)

function respond(int $status, array $payload): void {
  http_response_code($status);
  echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  exit;
}

// --- Auth
$token = $_GET['token'] ?? '';
if (!is_string($token) || $token === '' || !hash_equals(API_TOKEN, $token)) {
  respond(401, [
    'ok' => false,
    'error' => 'unauthorized',
  ]);
}

// --- PDO is expected to be available as $pdo.
// If you need to include your config, uncomment the line below:
// require_once __DIR__ . '/../config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  respond(500, [
    'ok' => false,
    'error' => 'pdo_not_available',
    'hint' => 'Make sure $pdo is defined before calling this script (or include your config).',
  ]);
}

try {
  // Non-exclusive buckets: "last X days" counts everything newer than NOW() - INTERVAL X DAY.
  $sql = "
    SELECT
      COUNT(*) AS total_keywords,

      SUM(CASE WHEN last_update IS NULL THEN 1 ELSE 0 END) AS last_update_null,
      SUM(CASE WHEN last_update >= (NOW() - INTERVAL 3 DAY) THEN 1 ELSE 0 END)  AS last_update_3d,
      SUM(CASE WHEN last_update >= (NOW() - INTERVAL 15 DAY) THEN 1 ELSE 0 END) AS last_update_15d,
      SUM(CASE WHEN last_update >= (NOW() - INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS last_update_30d,
      SUM(CASE WHEN last_update >= (NOW() - INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS last_update_90d,

      SUM(CASE WHEN last_visited IS NULL THEN 1 ELSE 0 END) AS last_visited_null,
      SUM(CASE WHEN last_visited >= (NOW() - INTERVAL 3 DAY) THEN 1 ELSE 0 END)  AS last_visited_3d,
      SUM(CASE WHEN last_visited >= (NOW() - INTERVAL 15 DAY) THEN 1 ELSE 0 END) AS last_visited_15d,
      SUM(CASE WHEN last_visited >= (NOW() - INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS last_visited_30d,
      SUM(CASE WHEN last_visited >= (NOW() - INTERVAL 90 DAY) THEN 1 ELSE 0 END) AS last_visited_90d
    FROM keywords
  ";

  $stmt = $pdo->query($sql);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    respond(500, ['ok' => false, 'error' => 'query_failed']);
  }

  $toInt = fn($v) => (int)($v ?? 0);

  respond(200, [
    'ok' => true,
    'generated_at' => gmdate('c'),
    'table' => 'keywords',
    'totals' => [
      'total_keywords' => $toInt($row['total_keywords']),
    ],
    'last_update' => [
      'updated_last_3_days'  => $toInt($row['last_update_3d']),
      'updated_last_15_days' => $toInt($row['last_update_15d']),
      'updated_last_30_days' => $toInt($row['last_update_30d']),
      'updated_last_90_days' => $toInt($row['last_update_90d']),
      'never_updated_null'   => $toInt($row['last_update_null']),
    ],
    'last_visited' => [
      'visited_last_3_days'  => $toInt($row['last_visited_3d']),
      'visited_last_15_days' => $toInt($row['last_visited_15d']),
      'visited_last_30_days' => $toInt($row['last_visited_30d']),
      'visited_last_90_days' => $toInt($row['last_visited_90d']),
      'never_visited_null'   => $toInt($row['last_visited_null']),
    ],
    'notes' => [
      'Buckets are non-exclusive (last_90 includes last_30, last_15, last_3).',
    ],
  ]);

} catch (Throwable $e) {
  respond(500, [
    'ok' => false,
    'error' => 'exception',
    'message' => $e->getMessage(),
  ]);
}
