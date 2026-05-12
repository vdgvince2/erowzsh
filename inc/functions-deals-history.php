<?php
/**
 * Price history for deal pages.
 *
 * Data flow:
 *   1. deals.php fetches live products from eBay Browse API (cache miss, 1h TTL).
 *   2. deals_record_snapshot() is called → computes stats, appends to a JSON file.
 *   3. The JSON file stores up to 180 hourly entries (~7.5 days).
 *   4. deals_aggregate_daily() reduces that to one entry per day for the chart.
 *   5. deals_smooth_medians() applies a 3-point moving average on the median line.
 *   6. deals_compute_trend() compares the last 3 days vs the first 3 days.
 *
 * Outlier removal: IQR method (remove prices below Q1-1.5*IQR or above Q3+1.5*IQR).
 * This keeps the median and bands meaningful even when eBay returns random high listings.
 */


// ── Outlier removal ────────────────────────────────────────────────────────────

/**
 * Remove price outliers using the IQR method.
 * Returns a sorted, filtered array of prices.
 */
function deals_remove_outliers(array $prices): array
{
    if (count($prices) < 4) return $prices;

    $sorted = $prices;
    sort($sorted);
    $n   = count($sorted);
    $q1  = $sorted[(int)floor($n * 0.25)];
    $q3  = $sorted[(int)floor($n * 0.75)];
    $iqr = $q3 - $q1;

    if ($iqr == 0) return $sorted; // all identical prices

    $lower = $q1 - 1.5 * $iqr;
    $upper = $q3 + 1.5 * $iqr;

    return array_values(array_filter($sorted, fn($p) => $p >= $lower && $p <= $upper));
}


// ── Stats ──────────────────────────────────────────────────────────────────────

/**
 * Compute price statistics from a product list.
 * Returns null if not enough data.
 *
 * @return array{median:float, p25:float, p75:float, min:float, max:float, count:int}|null
 */
function deals_compute_stats(array $products): ?array
{
    $prices = [];
    foreach ($products as $p) {
        $v = (float)($p['price'] ?? 0);
        if ($v > 0) $prices[] = $v;
    }

    if (empty($prices)) return null;

    $prices = deals_remove_outliers($prices);
    if (empty($prices)) return null;

    sort($prices);
    $n      = count($prices);
    $half   = (int)floor($n / 2);
    $median = ($n % 2 === 0)
        ? ($prices[$half - 1] + $prices[$half]) / 2
        : $prices[$half];

    return [
        'median' => round($median, 2),
        'p25'    => round($prices[(int)floor($n * 0.25)], 2),
        'p75'    => round($prices[(int)floor($n * 0.75)], 2),
        'min'    => round($prices[0], 2),
        'max'    => round($prices[$n - 1], 2),
        'count'  => $n,
    ];
}


// ── Snapshot recording ────────────────────────────────────────────────────────

/**
 * Append a price snapshot to the history file (at most once per hour).
 * The history file is independent of sort/filter params — always records
 * the raw "default" fetch stats so the trend is comparable over time.
 */
function deals_record_snapshot(string $historyFile, array $products): void
{
    $stats = deals_compute_stats($products);
    if (!$stats) return;

    $history = deals_load_history($historyFile);

    // Skip if the last entry is less than 1 hour old
    if (!empty($history)) {
        $last = end($history);
        if (isset($last['ts']) && (time() - (int)$last['ts']) < 3600) return;
    }

    $history[] = array_merge(['ts' => time(), 'date' => date('Y-m-d')], $stats);

    // Cap at 180 entries (~7.5 days of hourly snapshots)
    if (count($history) > 180) {
        $history = array_slice($history, -180);
    }

    @file_put_contents($historyFile, json_encode($history));
}


// ── Load history ──────────────────────────────────────────────────────────────

function deals_load_history(string $historyFile): array
{
    if (!file_exists($historyFile)) return [];
    $raw  = file_get_contents($historyFile);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}


// ── Aggregate to daily ────────────────────────────────────────────────────────

/**
 * Reduce hourly entries to one entry per day (last snapshot of the day).
 * Returns at most $maxDays days, ordered oldest → newest.
 */
function deals_aggregate_daily(array $history, int $maxDays = 30): array
{
    if (empty($history)) return [];

    $byDate = [];
    foreach ($history as $entry) {
        $date = $entry['date'] ?? date('Y-m-d', (int)($entry['ts'] ?? 0));
        $byDate[$date] = $entry; // last entry of the day wins
    }

    ksort($byDate);

    $days = array_values($byDate);
    if (count($days) > $maxDays) {
        $days = array_slice($days, -$maxDays);
    }

    return $days;
}


// ── Smoothing ─────────────────────────────────────────────────────────────────

/**
 * Apply a 3-point centred moving average to the median field.
 * Adds a 'median_smooth' key to each entry.
 * Endpoints fall back to the raw median.
 */
function deals_smooth_medians(array $daily): array
{
    $n = count($daily);
    if ($n < 3) {
        foreach ($daily as &$d) $d['median_smooth'] = $d['median'];
        return $daily;
    }

    $daily[0]['median_smooth']       = $daily[0]['median'];
    $daily[$n - 1]['median_smooth']  = $daily[$n - 1]['median'];

    for ($i = 1; $i < $n - 1; $i++) {
        $daily[$i]['median_smooth'] = round(
            ($daily[$i-1]['median'] + $daily[$i]['median'] + $daily[$i+1]['median']) / 3,
            2
        );
    }

    return $daily;
}


// ── Trend ─────────────────────────────────────────────────────────────────────

/**
 * Compare the average of the last 3 days vs the first 3 days.
 *
 * @return array{direction: 'up'|'down'|'stable', pct: float}
 */
function deals_compute_trend(array $daily): array
{
    $n = count($daily);
    if ($n < 2) return ['direction' => 'stable', 'pct' => 0.0];

    $take   = min(3, $n);
    $recent = array_slice($daily, -$take);
    $old    = array_slice($daily,  0, $take);

    $recentAvg = array_sum(array_column($recent, 'median')) / count($recent);
    $oldAvg    = array_sum(array_column($old,    'median')) / count($old);

    if ($oldAvg == 0) return ['direction' => 'stable', 'pct' => 0.0];

    $pct = (($recentAvg - $oldAvg) / $oldAvg) * 100;

    return [
        'direction' => $pct > 5 ? 'up' : ($pct < -5 ? 'down' : 'stable'),
        'pct'       => round(abs($pct), 1),
    ];
}


// ── Market demand snapshots (DB) ──────────────────────────────────────────────

/**
 * Ensure the deals_market_snapshots table exists (auto-create on first call).
 */
function deals_ensure_market_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deals_market_snapshots (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            slug_key         VARCHAR(255)   NOT NULL,
            snap_date        DATE           NOT NULL,
            watch_total      INT            NOT NULL DEFAULT 0,
            bid_total        INT            NOT NULL DEFAULT 0,
            free_ship_pct    DECIMAL(5,2)   NOT NULL DEFAULT 0,
            avg_discount_pct DECIMAL(5,2)   NOT NULL DEFAULT 0,
            top_rated_count  INT            NOT NULL DEFAULT 0,
            listings_count   INT            NOT NULL DEFAULT 0,
            created_at       TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_slug_date (slug_key(200), snap_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/**
 * Record one market snapshot per day for a keyword (skip if already recorded today).
 * Aggregates watchCount, bidCount, free-shipping %, avg discount %, top-rated count.
 */
function deals_record_market_snapshot(PDO $pdo, string $slugKey, array $products): void
{
    if (empty($products)) return;

    deals_ensure_market_table($pdo);

    $today = date('Y-m-d');

    // Skip if we already have today's entry
    $check = $pdo->prepare("SELECT id FROM deals_market_snapshots WHERE slug_key = ? AND snap_date = ? LIMIT 1");
    $check->execute([$slugKey, $today]);
    if ($check->fetch()) return;

    $watchTotal    = 0;
    $bidTotal      = 0;
    $freeShipCount = 0;
    $discountSum   = 0.0;
    $discountCount = 0;
    $topRatedCount = 0;
    $total         = count($products);

    foreach ($products as $p) {
        $watchTotal += (int)($p['watch_count'] ?? 0);
        $bidTotal   += (int)($p['bid_count']   ?? 0);
        if (!empty($p['is_free_shipping'])) $freeShipCount++;
        if (!empty($p['marketing_discount_pct'])) {
            $discountSum += (float)$p['marketing_discount_pct'];
            $discountCount++;
        }
        if (!empty($p['top_rated'])) $topRatedCount++;
    }

    $freeShipPct    = $total > 0 ? round($freeShipCount / $total * 100, 2) : 0.0;
    $avgDiscountPct = $discountCount > 0 ? round($discountSum / $discountCount, 2) : 0.0;

    $stmt = $pdo->prepare("
        INSERT INTO deals_market_snapshots
            (slug_key, snap_date, watch_total, bid_total, free_ship_pct, avg_discount_pct, top_rated_count, listings_count)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            watch_total      = VALUES(watch_total),
            bid_total        = VALUES(bid_total),
            free_ship_pct    = VALUES(free_ship_pct),
            avg_discount_pct = VALUES(avg_discount_pct),
            top_rated_count  = VALUES(top_rated_count),
            listings_count   = VALUES(listings_count)
    ");
    $stmt->execute([$slugKey, $today, $watchTotal, $bidTotal, $freeShipPct, $avgDiscountPct, $topRatedCount, $total]);
}

/**
 * Load the last $days days of market snapshots for a slug key.
 *
 * @return array  Ordered oldest → newest, each entry:
 *                {snap_date, watch_total, bid_total, free_ship_pct, avg_discount_pct, top_rated_count, listings_count}
 */
function deals_load_market_history(PDO $pdo, string $slugKey, int $days = 7): array
{
    deals_ensure_market_table($pdo);

    $stmt = $pdo->prepare("
        SELECT snap_date, watch_total, bid_total, free_ship_pct, avg_discount_pct, top_rated_count, listings_count
        FROM deals_market_snapshots
        WHERE slug_key = ?
        ORDER BY snap_date DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $slugKey);
    $stmt->bindValue(2, $days, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return array_reverse($rows); // oldest → newest
}
