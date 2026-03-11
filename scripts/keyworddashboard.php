<?php
declare(strict_types=1);

// ======= CONFIG
const API_TOKEN = 'FJKWrRptk7vOEv4jxuxvWQqJif26RIHN';

$sites = [
  'https://www.site-annonce.be',
  'https://www.for-sale.ie',
  'https://www.for-sale.co.uk',
  'https://www.used.forsale',
  'https://www.gebraucht-kaufen.de',
  'https://www.in-vendita.it',
];

$endpointPath = '/inc/keywords_stats.php';

// ======= FETCH (parallel)
function fetch_multi(array $urls, int $timeoutSeconds = 12): array {
  $mh = curl_multi_init();
  $handles = [];
  $results = [];

  foreach ($urls as $key => $url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
      CURLOPT_TIMEOUT => $timeoutSeconds,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_HTTPHEADER => [
        'Accept: application/json',
      ],
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[$key] = $ch;
  }

  $running = null;
  do {
    $status = curl_multi_exec($mh, $running);
    if ($running) {
      curl_multi_select($mh, 1.0);
    }
  } while ($running && $status === CURLM_OK);

  foreach ($handles as $key => $ch) {
    $body = curl_multi_getcontent($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    $results[$key] = [
      'http_code' => $code,
      'error' => $err ?: null,
      'raw' => $body,
    ];

    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
  }

  curl_multi_close($mh);
  return $results;
}

$urls = [];
foreach ($sites as $site) {
  $urls[$site] = rtrim($site, '/') . $endpointPath . '?token=' . urlencode(API_TOKEN);
}

$responses = fetch_multi($urls);

$data = [];
foreach ($responses as $site => $resp) {
  $row = [
    'site' => $site,
    'status' => 'ok',
    'http_code' => $resp['http_code'],
    'total' => null,

    'u3' => null, 'u15' => null, 'u30' => null, 'u90' => null, 'uNever' => null,
    'v3' => null, 'v15' => null, 'v30' => null, 'v90' => null, 'vNever' => null,

    'error' => null,
  ];

  if ($resp['error']) {
    $row['status'] = 'error';
    $row['error'] = 'cURL: ' . $resp['error'];
    $data[] = $row;
    continue;
  }

  if ($resp['http_code'] < 200 || $resp['http_code'] >= 300) {
    $row['status'] = 'error';
    $row['error'] = 'HTTP ' . $resp['http_code'];
    $data[] = $row;
    continue;
  }

  $json = json_decode($resp['raw'], true);
  if (!is_array($json) || !($json['ok'] ?? false)) {
    $row['status'] = 'error';
    $row['error'] = 'Invalid JSON or ok=false';
    $data[] = $row;
    continue;
  }

  $row['total'] = (int)($json['totals']['total_keywords'] ?? 0);

  $row['u3']     = (int)($json['last_update']['updated_last_3_days'] ?? 0);
  $row['u15']    = (int)($json['last_update']['updated_last_15_days'] ?? 0);
  $row['u30']    = (int)($json['last_update']['updated_last_30_days'] ?? 0);
  $row['u90']    = (int)($json['last_update']['updated_last_90_days'] ?? 0);
  $row['uNever'] = (int)($json['last_update']['never_updated_null'] ?? 0);

  $row['v3']     = (int)($json['last_visited']['visited_last_3_days'] ?? 0);
  $row['v15']    = (int)($json['last_visited']['visited_last_15_days'] ?? 0);
  $row['v30']    = (int)($json['last_visited']['visited_last_30_days'] ?? 0);
  $row['v90']    = (int)($json['last_visited']['visited_last_90_days'] ?? 0);
  $row['vNever'] = (int)($json['last_visited']['never_visited_null'] ?? 0);

  $data[] = $row;
}

// sort by site
usort($data, fn($a, $b) => strcmp($a['site'], $b['site']));

function badge(string $status): string {
  if ($status === 'ok') return '<span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">OK</span>';
  return '<span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">ERROR</span>';
}

?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Keywords freshness dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
  <div class="mx-auto max-w-7xl p-6">
    <div class="mb-6 flex flex-col gap-2">
      <h1 class="text-2xl font-semibold tracking-tight">Keywords — Freshness & Visits</h1>
      <p class="text-sm text-slate-600">
        Buckets “last X days” are non-exclusive (90 includes 30/15/3). Generated locally at: <span class="font-mono"><?= htmlspecialchars(date('c')) ?></span>
      </p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Site</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Total</th>

              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Upd 3d</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Upd 15d</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Upd 30d</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Upd 90d</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Upd NULL</th>

              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Vis 3d</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Vis 15d</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Vis 30d</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Vis 90d</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Vis NULL</th>

              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Error</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200">
            <?php foreach ($data as $r): ?>
              <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 text-sm font-medium">
                  <a class="text-slate-900 hover:underline" href="<?= htmlspecialchars($r['site']) ?>" target="_blank" rel="noreferrer">
                    <?= htmlspecialchars($r['site']) ?>
                  </a>
                </td>

                <td class="px-4 py-3 text-sm">
                  <?= badge($r['status']) ?>
                  <span class="ml-2 text-xs text-slate-500">(<?= (int)$r['http_code'] ?>)</span>
                </td>

                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['total'] !== null ? number_format((int)$r['total'], 0, ',', ' ') : '—' ?></td>

                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['u3'] !== null ? number_format((int)$r['u3'], 0, ',', ' ') : '—' ?></td>
                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['u15'] !== null ? number_format((int)$r['u15'], 0, ',', ' ') : '—' ?></td>
                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['u30'] !== null ? number_format((int)$r['u30'], 0, ',', ' ') : '—' ?></td>
                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['u90'] !== null ? number_format((int)$r['u90'], 0, ',', ' ') : '—' ?></td>
                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['uNever'] !== null ? number_format((int)$r['uNever'], 0, ',', ' ') : '—' ?></td>

                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['v3'] !== null ? number_format((int)$r['v3'], 0, ',', ' ') : '—' ?></td>
                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['v15'] !== null ? number_format((int)$r['v15'], 0, ',', ' ') : '—' ?></td>
                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['v30'] !== null ? number_format((int)$r['v30'], 0, ',', ' ') : '—' ?></td>
                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['v90'] !== null ? number_format((int)$r['v90'], 0, ',', ' ') : '—' ?></td>
                <td class="px-4 py-3 text-sm text-right font-mono whitespace-nowrap"><?= $r['vNever'] !== null ? number_format((int)$r['vNever'], 0, ',', ' ') : '—' ?></td>

                <td class="px-4 py-3 text-sm text-slate-600">
                  <?= $r['error'] ? htmlspecialchars($r['error']) : '<span class="text-slate-300">—</span>' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="border-t border-slate-200 bg-white p-4 text-xs text-slate-500">
        Endpoint called: <span class="font-mono"><?= htmlspecialchars($endpointPath) ?></span>
      </div>
    </div>
  </div>
</body>
</html>
