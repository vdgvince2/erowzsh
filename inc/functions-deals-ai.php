<?php
/**
 * Grok AI content generation + DB caching for /deals/ pages.
 *
 * Flow:
 *  1. deals_ai_load()  — checks DB, returns cached content if fresh (7-day TTL)
 *  2. deals_ai_generate() — calls Grok API, returns decoded array
 *  3. deals_ai_render() — outputs the HTML expert section
 *  4. deals_ai_jsonld() — outputs FAQPage JSON-LD
 */

define('GROQ_API_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_API_KEY',      getenv('GROQ_API_KEY') ?: '');
define('GROQ_MODEL',        'llama-3.3-70b-versatile');
define('DEALS_AI_TTL',      604800); // 7 days

// ── Table bootstrap ──────────────────────────────────────────────────────────

function deals_ai_ensure_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS `deals_ai_content` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `slug_key`     VARCHAR(255) NOT NULL,
        `content_json` MEDIUMTEXT   NOT NULL,
        `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_slug` (`slug_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}


// ── Load (with 7-day cache) ──────────────────────────────────────────────────

/**
 * Returns ['data' => [...], 'updated_at' => 'Y-m-d H:i:s'] or null.
 */
function deals_ai_load(
    PDO    $pdo,
    string $slugKey,
    string $keyword,
    string $language,
    string $countryCode,
    string $currency,
    array  $products,
    ?array $latestSnap,
    array  $priceTrend
): ?array {
    deals_ai_ensure_table($pdo);

    // Check DB cache
    $stmt = $pdo->prepare(
        "SELECT content_json, updated_at FROM deals_ai_content WHERE slug_key = :key LIMIT 1"
    );
    $stmt->execute([':key' => $slugKey]);
    $row = $stmt->fetch();

    if ($row) {
        $age  = time() - strtotime($row['updated_at']);
        $data = json_decode($row['content_json'], true);
        if ($age < DEALS_AI_TTL && is_array($data) && !empty($data['intro'])) {
            return ['data' => $data, 'updated_at' => $row['updated_at']];
        }
    }

    // Generate via Grok
    $data = deals_ai_generate($keyword, $language, $countryCode, $currency, $products, $latestSnap, $priceTrend);
    if (!$data) return null;

    // Upsert
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $pdo->prepare(
        "INSERT INTO deals_ai_content (slug_key, content_json) VALUES (:key, :json)
         ON DUPLICATE KEY UPDATE content_json = VALUES(content_json), updated_at = NOW()"
    )->execute([':key' => $slugKey, ':json' => $json]);

    return ['data' => $data, 'updated_at' => date('Y-m-d H:i:s')];
}


// ── Grok API call ────────────────────────────────────────────────────────────

function deals_ai_generate(
    string $keyword,
    string $language,
    string $countryCode,
    string $currency,
    array  $products,
    ?array $latestSnap,
    array  $priceTrend
): ?array {
    $langNames = ['EN' => 'English', 'FR' => 'French', 'DE' => 'German', 'IT' => 'Italian'];
    $langName  = $langNames[$language] ?? 'English';

    // Price context from history snapshot or live products
    $priceCtx = '';
    if ($latestSnap && !empty($latestSnap['median'])) {
        $priceCtx = "Median price: {$currency}" . number_format($latestSnap['median'], 0)
            . ", typical range {$currency}" . number_format($latestSnap['p25'], 0)
            . "–{$currency}" . number_format($latestSnap['p75'], 0)
            . " (" . ($latestSnap['count'] ?? '?') . " listings tracked).";
    } elseif (!empty($products)) {
        $prices = array_filter(array_map(fn($p) => (float)($p['price'] ?? 0), $products), fn($v) => $v > 0);
        if ($prices) {
            $priceCtx = count($prices) . " listings ranging from {$currency}" . number_format(min($prices), 0)
                . " to {$currency}" . number_format(max($prices), 0) . ".";
        }
    }

    $trendCtx = match ($priceTrend['direction']) {
        'up'    => "⚠ Prices are currently rising (+{$priceTrend['pct']}%). Buying soon is advisable.",
        'down'  => "✓ Prices are falling (-{$priceTrend['pct']}%). Good time to wait a few more days.",
        default => "Prices are stable — no urgency either way.",
    };

    // Top 5 sample listings with prices
    $samples = [];
    foreach (array_slice($products, 0, 5) as $p) {
        $title = trim($p['title_original'] ?? '');
        $price = (float)($p['price'] ?? 0);
        if ($title && $price > 0) {
            $samples[] = '- ' . mb_substr($title, 0, 80) . ' → ' . $currency . number_format($price, 0);
        }
    }
    $samplesBlock = $samples ? implode("\n", $samples) : '(no samples)';

    $prompt = <<<PROMPT
You are a senior deals analyst writing expert buyer guides for a second-hand marketplace. Write a guide for "{$keyword}" for buyers in {$countryCode}. Language: {$langName}.

Live eBay market data (today):
{$priceCtx}
Trend: {$trendCtx}
Current listings sample:
{$samplesBlock}

Return ONLY valid JSON — no markdown, no extra text outside the braces:
{
  "intro": "2-3 sentences. Expert tone. Mention actual price range. Tell buyers what matters most right now.",
  "key_specs": [
    "One concrete thing to inspect or verify before buying a used {$keyword}.",
    "A second specific quality or compatibility point that matters.",
    "A third practical tip (warranty, accessories, seller type, etc.)."
  ],
  "qa": [
    {"q": "Specific question a buyer of {$keyword} would ask?", "a": "Precise answer, reference prices if relevant."},
    {"q": "How to spot the best value {$keyword}?", "a": "Concrete criteria."},
    {"q": "New vs used/refurbished {$keyword} — is it worth it?", "a": "Honest trade-off analysis."}
  ],
  "faq": [
    {"q": "Is it safe to buy a used {$keyword} online?", "a": "What to check: seller rating, return policy, condition."},
    {"q": "What is a fair price for {$keyword} right now?", "a": "Based on the data above."}
  ],
  "buying_tip": "One actionable tip based on the current market data. Be specific."
}

Constraints: {$langName} only. Non-generic. Reference real prices. Max 700 tokens.
PROMPT;

    $ch = curl_init(GROQ_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model'       => GROQ_MODEL,
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'max_tokens'  => 900,
            'temperature' => 0.45,
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_TIMEOUT => 18,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return null;

    $resp = json_decode($response, true);
    $text = $resp['choices'][0]['message']['content'] ?? '';

    // Extract the JSON object from the response
    if (preg_match('/\{[\s\S]*\}/u', $text, $m)) {
        $data = json_decode($m[0], true);
        if (is_array($data) && !empty($data['intro']) && !empty($data['qa'])) {
            return $data;
        }
    }

    return null;
}


// ── HTML render ──────────────────────────────────────────────────────────────

function deals_ai_render(array $data, string $keyword, string $updatedAt, string $language = 'EN'): void
{
    $intro     = $data['intro']      ?? '';
    $buyingTip = $data['buying_tip'] ?? '';
    $qaItems   = $data['qa']         ?? [];
    $faqItems  = $data['faq']        ?? [];

    $dateFormatted = date('F j, Y', strtotime($updatedAt));

    $labelQA      = ['EN' => 'Questions & Answers', 'FR' => 'Questions & Réponses',   'DE' => 'Fragen & Antworten',     'IT' => 'Domande & Risposte'][$language]     ?? 'Questions & Answers';
    $labelFAQ     = 'FAQ';
    $labelTip     = ['EN' => '💡 Expert tip',       'FR' => '💡 Conseil expert',       'DE' => '💡 Expertentipp',        'IT' => '💡 Consiglio esperto'][$language]   ?? '💡 Expert tip';
    $labelUpdated = ['EN' => 'Updated',             'FR' => 'Mis à jour',              'DE' => 'Aktualisiert',           'IT' => 'Aggiornato'][$language]             ?? 'Updated';
    $labelExpert  = ['EN' => 'Marketplace & deals expert', 'FR' => 'Expert marketplace & deals', 'DE' => 'Marktplatz- & Deals-Experte', 'IT' => 'Esperto marketplace & deals'][$language] ?? 'Marketplace & deals expert';
    ?>

    <div class="mt-10 border-t border-gray-200 pt-8" id="expert-guide">

        <!-- Author + date -->
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 select-none">VV</div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-800 flex flex-wrap items-center gap-x-1.5">
                    <a href="https://www.linkedin.com/in/vincentvandegans/" target="_blank" rel="noopener noreferrer"
                       class="hover:text-blue-600 underline decoration-dotted underline-offset-2">Vincent Vandegans</a>
                    <span class="text-gray-300">·</span>
                    <span class="text-gray-500 font-normal text-xs"><?= htmlspecialchars($labelExpert, ENT_QUOTES) ?></span>
                </p>
                <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($labelUpdated, ENT_QUOTES) ?>: <?= htmlspecialchars($dateFormatted, ENT_QUOTES) ?></p>
            </div>
        </div>

        <!-- Expert intro -->
        <?php if ($intro): ?>
        <p class="text-sm text-gray-700 leading-relaxed mb-6">
            <?= htmlspecialchars($intro, ENT_QUOTES) ?>
        </p>
        <?php endif; ?>

        <!-- Q&A (accordion) -->
        <?php if (!empty($qaItems)): ?>
        <section class="mb-6">
            <h2 class="text-base font-semibold text-gray-800 mb-3"><?= htmlspecialchars($labelQA, ENT_QUOTES) ?></h2>
            <div class="space-y-2">
                <?php foreach ($qaItems as $i => $item):
                    $q = htmlspecialchars($item['q'] ?? '', ENT_QUOTES);
                    $a = htmlspecialchars($item['a'] ?? '', ENT_QUOTES);
                    if (!$q || !$a) continue;
                ?>
                <details class="bg-gray-50 rounded-xl overflow-hidden border border-gray-100 group" <?= $i === 0 ? 'open' : '' ?>>
                    <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-gray-800 flex items-start justify-between gap-3 select-none list-none marker:hidden [&::-webkit-details-marker]:hidden">
                        <span class="flex-1"><?= $q ?></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="px-4 pb-4 pt-1 text-sm text-gray-600 leading-relaxed border-t border-gray-100"><?= $a ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Buying tip -->
        <?php if ($buyingTip): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <p class="text-sm font-semibold text-blue-800 mb-1"><?= htmlspecialchars($labelTip, ENT_QUOTES) ?></p>
            <p class="text-sm text-blue-700 leading-relaxed"><?= htmlspecialchars($buyingTip, ENT_QUOTES) ?></p>
        </div>
        <?php endif; ?>

        <!-- FAQ (accordion) -->
        <?php if (!empty($faqItems)): ?>
        <section>
            <h2 class="text-base font-semibold text-gray-800 mb-3"><?= htmlspecialchars($labelFAQ, ENT_QUOTES) ?></h2>
            <div class="space-y-2">
                <?php foreach ($faqItems as $item):
                    $q = htmlspecialchars($item['q'] ?? '', ENT_QUOTES);
                    $a = htmlspecialchars($item['a'] ?? '', ENT_QUOTES);
                    if (!$q || !$a) continue;
                ?>
                <details class="border border-gray-200 rounded-xl overflow-hidden group">
                    <summary class="px-4 py-3 cursor-pointer text-sm font-medium text-gray-700 flex items-start justify-between gap-3 select-none list-none marker:hidden [&::-webkit-details-marker]:hidden">
                        <span class="flex-1"><?= $q ?></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="px-4 pb-4 pt-1 text-sm text-gray-600 leading-relaxed border-t border-gray-100"><?= $a ?></div>
                </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>
    <?php
}


// ── FAQPage JSON-LD ──────────────────────────────────────────────────────────

function deals_ai_jsonld(array $data, string $pageUrl): void
{
    $allItems = array_merge($data['qa'] ?? [], $data['faq'] ?? []);
    if (empty($allItems)) return;

    $elements = [];
    foreach ($allItems as $item) {
        $q = $item['q'] ?? '';
        $a = $item['a'] ?? '';
        if (!$q || !$a) continue;
        $elements[] = '{
      "@type": "Question",
      "name": "' . addslashes($q) . '",
      "acceptedAnswer": {"@type": "Answer", "text": "' . addslashes($a) . '"}
    }';
    }

    if (empty($elements)) return;
    ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "url": "<?= htmlspecialchars($pageUrl) ?>",
  "mainEntity": [
    <?= implode(",\n    ", $elements) ?>
  ]
}
</script>
    <?php
}
