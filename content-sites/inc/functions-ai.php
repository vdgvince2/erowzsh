<?php
/**
 * Content-Sites — Claude API (Anthropic)
 *
 * Génère un article de guide d'achat pour une sous-niche donnée,
 * avec intégration structurée des produits eBay et blocs EEAT.
 *
 * Utilise claude-sonnet-4-6 via l'API Messages d'Anthropic.
 * Prompt caching activé sur le system prompt (TTL 5 min).
 */

/**
 * Construit le system prompt (mis en cache côté API).
 * Décrit la structure imposée de l'article et les règles EEAT.
 */
function cs_ai_system_prompt(string $language, string $countryLabel, string $today): string
{
    $languageNames = [
        'EN' => 'English',
        'FR' => 'French',
        'DE' => 'German',
        'IT' => 'Italian',
    ];
    $langName = $languageNames[$language] ?? 'English';

    return "You are an expert content writer specialising in buying guides for {$countryLabel}.
Tone: authoritative, helpful, trustworthy (EEAT).
Output ONLY the inner body HTML — no <!DOCTYPE>, no <html>, no <head>, no <body> tags, no markdown, no code fences, no commentary.
The output must start directly with <h1> and contain only these allowed elements: h1, h2, h3, p, ul, ol, li, strong, em, a, details, summary, time, div, span, <!-- comments -->.
IMPORTANT: You MUST write the entire article in {$langName}. Every word of content must be in {$langName}.

Article structure (use exactly this order):
1. <h1> — buying guide title
2. <div class=\"article-intro\"> — 2-paragraph expert introduction with key context
3. <div class=\"eeat-author\"> — short expert author bio (EEAT signal)
4. <h2> — \"What to look for in [sub-niche]\" section
5. <ul class=\"buying-criteria\"> — 4-6 buying criteria with <li><strong>criterion</strong>: explanation</li>
6. <!-- PRODUCT_BLOCK_1 --> placeholder (replaced by product embed)
7. <h2> — \"Top picks: [sub-niche] in {$countryLabel}\" section
8. <!-- PRODUCT_BLOCK_2 --> placeholder
9. <h2> — \"Where to buy [sub-niche]\" section — mention eBay specifically
10. <!-- PRODUCT_BLOCK_3 --> placeholder
11. <h2> — FAQ (4 questions minimum, use <details><summary>Q</summary>A</details>)
12. <div class=\"eeat-trust\"> — trust signals with <time datetime=\"{$today}\">{$today}</time> — use this exact date, never invent a date
13. <div class=\"article-conclusion\"> — conclusion paragraph with CTA

Rules:
- Every <h2> must have a unique, keyword-rich heading.
- Do NOT invent prices or product names — the real products are injected via placeholders.
- Do NOT use placeholder text like [KEYWORD] or [COUNTRY].
- The article must be at least 900 words of real content (excluding product blocks).
- Be empathic, nice, emotional.
- Show your expertise along the writing.";
}

/**
 * Construit le user prompt : contexte de la sous-niche + liste produits eBay.
 */
function cs_ai_user_prompt(
    string $subNicheName,
    string $nicheName,
    string $language,
    string $countryLabel,
    array  $products
): string {
    $productList = '';
    foreach ($products as $i => $p) {
        $pos = $i + 1;
        $productList .= "  {$pos}. \"{$p['title']}\" — {$p['currency']}{$p['price']}\n";
    }

    $languageNames = [
        'EN' => 'English',
        'FR' => 'French',
        'DE' => 'German',
        'IT' => 'Italian',
    ];
    $langName = $languageNames[$language] ?? 'English';

    return "Write a complete buying guide for: **{$subNicheName}** (part of the {$nicheName} niche) for buyers in {$countryLabel}.

IMPORTANT: The entire article MUST be written in {$langName}. Do not use any other language.

The article MUST include exactly 3 product block placeholders: <!-- PRODUCT_BLOCK_1 -->, <!-- PRODUCT_BLOCK_2 -->, <!-- PRODUCT_BLOCK_3 -->.
Place them naturally within the article flow as described in the structure.

The following {$countryLabel} eBay listings will replace the placeholders (do not describe them in text — just place the placeholder):
{$productList}
Generate the full article HTML now.";
}

/**
 * Appel à l'API Claude Sonnet pour générer l'article.
 * Retourne le HTML brut ou null en cas d'erreur.
 *
 * @param string $subNicheName
 * @param string $nicheName
 * @param string $language
 * @param string $countryLabel
 * @param array  $products      Résultat de cs_fetch_ebay_products()
 */
function cs_generate_article_html(
    string $subNicheName,
    string $nicheName,
    string $language,
    string $countryLabel,
    array  $products
): ?string {
    $apiKey = ANTHROPIC_API_KEY;
    if (empty($apiKey)) {
        error_log('[cs_ai] ANTHROPIC_API_KEY manquante');
        return null;
    }

    $today        = date('Y-m-d');
    $systemPrompt = cs_ai_system_prompt($language, $countryLabel, $today);
    $userPrompt   = cs_ai_user_prompt($subNicheName, $nicheName, $language, $countryLabel, $products);

    $payload = json_encode([
        'model'      => ANTHROPIC_MODEL,
        'max_tokens' => 4096,
        'system'     => [
            [
                'type' => 'text',
                'text' => $systemPrompt,
                // Cache le system prompt (TTL 5 min côté Anthropic)
                'cache_control' => ['type' => 'ephemeral'],
            ]
        ],
        'messages'   => [
            ['role' => 'user', 'content' => $userPrompt],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'anthropic-beta: prompt-caching-2024-07-31',
        ],
        CURLOPT_TIMEOUT        => 120,
    ]);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false || $http >= 400) {
        error_log("[cs_ai] Erreur API Claude HTTP {$http} — curl: {$err} — body: {$resp}");
        return null;
    }

    $json = json_decode($resp, true);
    $html = $json['content'][0]['text'] ?? null;

    if (empty($html)) {
        error_log('[cs_ai] Réponse vide ou inattendue : ' . json_encode($json));
        return null;
    }

    // Retire d'éventuelles balises de code que le modèle aurait ajoutées
    $html = preg_replace('/^```html\s*/i', '', trim($html));
    $html = preg_replace('/```\s*$/', '', $html);

    // Retire les wrappers html/head/body si le modèle les a ajoutés malgré les consignes
    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
    $html = preg_replace('/<head\b[^>]*>.*?<\/head>/si', '', $html);
    $html = preg_replace('/<\/?(?:html|body)\b[^>]*>/i', '', $html);

    return trim($html);
}

// ── Génération de keywords (pré-génération à l'avance) ────────────────────────

/**
 * System prompt pour la génération de keywords (mis en cache côté API).
 */
function cs_ai_keyword_system_prompt(): string
{
    return "You are an expert SEO content strategist specialising in informational article titles.
Your task: given a niche/sub-niche, generate article title candidates and evaluate each for search intent.

Scoring rules (0-100):
- 90-100: purely informational (how to, guide, history, differences, care, authentication)
- 70-89: mostly informational, some research intent
- 60-69: mixed but skews educational
- Below 60: transactional or commercial — EXCLUDE from output

Output format: ONLY a valid JSON array, no preamble, no trailing text.
[{\"title\": \"...\", \"intent\": \"informational|mixed\", \"score\": 85}, ...]
Order by score descending. Include only titles with score >= 60.";
}

/**
 * User prompt pour la génération de keywords dans la langue cible.
 */
function cs_ai_keyword_user_prompt(
    string $subNicheName,
    string $nicheName,
    string $language,
    string $countryLabel
): string {
    $languageNames = [
        'EN' => 'British English',
        'FR' => 'French',
        'DE' => 'German',
        'IT' => 'Italian',
        'BE' => 'French (Belgian)',
        'IE' => 'English (Irish)',
    ];
    $langName = $languageNames[$language] ?? 'English';

    $angles = [
        'EN' => "beginner's guide, how to authenticate, history and origins, style differences (e.g. Victorian vs Edwardian), how to care and maintain, common mistakes to avoid, what makes it valuable, how to spot fakes, restoration tips, glossary of terms",
        'FR' => "guide du débutant, comment authentifier, histoire et origines, différences de styles (ex : victorien vs édouardien), comment entretenir, erreurs courantes à éviter, ce qui le rend précieux, comment repérer les faux, conseils de restauration, glossaire",
        'DE' => "Anfängerleitfaden, Authentifizierung, Geschichte und Ursprünge, Stilunterschiede (z.B. viktorianisch vs. edwardianisch), Pflege und Wartung, häufige Fehler, Wertfaktoren, Fälschungen erkennen, Restaurierungstipps, Glossar",
        'IT' => "guida per principianti, come autenticare, storia e origini, differenze di stile (es. vittoriano vs edoardiano), come prendersi cura, errori comuni, fattori di valore, come riconoscere i falsi, consigli di restauro, glossario",
        'BE' => "guide du débutant, comment authentifier, histoire et origines, différences de styles, comment entretenir, erreurs courantes, ce qui le rend précieux, comment repérer les faux, conseils de restauration, glossaire",
        'IE' => "beginner's guide, how to authenticate, history and origins, style differences, how to care and maintain, common mistakes to avoid, what makes it valuable, how to spot fakes, restoration tips, glossary of terms",
    ];
    $angle = $angles[$language] ?? $angles['EN'];

    return "Sub-niche: \"{$subNicheName}\"
Niche: \"{$nicheName}\"
Country: {$countryLabel}
Target language: {$langName}

Generate 15 article title candidates for this sub-niche.
ALL titles MUST be written in {$langName} — never translate, write natively.
Use these angles as inspiration: {$angle}

Evaluate each title's search intent and return ONLY the JSON array (no other text).
Exclude any titles with commercial/transactional intent (buy, shop, price, deal, sale, discount).";
}

/**
 * Appel à l'API Claude pour générer et scorer des titres candidats.
 * Retourne un tableau de [{title, intent, score}] ou [] en cas d'erreur.
 */
function cs_generate_keywords_for_subniche(
    string $subNicheName,
    string $nicheName,
    string $language,
    string $countryLabel
): array {
    $apiKey = ANTHROPIC_API_KEY;
    if (empty($apiKey)) {
        error_log('[cs_ai_kw] ANTHROPIC_API_KEY manquante');
        return [];
    }

    $payload = json_encode([
        'model'      => ANTHROPIC_MODEL,
        'max_tokens' => 1024,
        'system'     => [
            [
                'type'          => 'text',
                'text'          => cs_ai_keyword_system_prompt(),
                'cache_control' => ['type' => 'ephemeral'],
            ]
        ],
        'messages' => [
            ['role' => 'user', 'content' => cs_ai_keyword_user_prompt(
                $subNicheName, $nicheName, $language, $countryLabel
            )],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'anthropic-beta: prompt-caching-2024-07-31',
        ],
        CURLOPT_TIMEOUT => 60,
    ]);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false || $http >= 400) {
        error_log("[cs_ai_kw] Erreur API Claude HTTP {$http} — curl: {$err} — body: {$resp}");
        return [];
    }

    $json = json_decode($resp, true);
    $text = $json['content'][0]['text'] ?? '';

    if (empty($text)) {
        error_log('[cs_ai_kw] Réponse vide : ' . json_encode($json));
        return [];
    }

    // Extrait le tableau JSON de la réponse (Claude peut ajouter du texte autour)
    if (!preg_match('/\[\s*\{.*?\}\s*\]/s', $text, $m)) {
        error_log('[cs_ai_kw] Impossible de parser le JSON : ' . $text);
        return [];
    }

    $keywords = json_decode($m[0], true);
    if (!is_array($keywords)) {
        error_log('[cs_ai_kw] JSON invalide : ' . $m[0]);
        return [];
    }

    // Normalise les champs
    return array_values(array_filter(array_map(function (array $kw): ?array {
        if (empty($kw['title'])) return null;
        return [
            'title'  => mb_substr(trim($kw['title']), 0, 300),
            'intent' => in_array($kw['intent'] ?? '', ['informational', 'mixed', 'transactional'])
                ? $kw['intent']
                : 'informational',
            'score'  => max(0, min(100, (int) ($kw['score'] ?? 80))),
        ];
    }, $keywords)));
}

/**
 * Génère le titre SEO de l'article selon la langue et le pays.
 */
function cs_generate_article_title(
    string $subNicheName,
    string $language,
    string $countryLabel
): string {
    $templates = [
        'EN' => "Guide to Buying the Best {subNiche} in {country}",
        'FR' => "Guide d'achat : les meilleurs {subNiche} en {country}",
        'DE' => "Kaufratgeber: Die besten {subNiche} in {country}",
        'IT' => "Guida all'acquisto: i migliori {subNiche} in {country}",
    ];
    $tpl = $templates[$language] ?? $templates['EN'];
    return str_replace(['{subNiche}', '{country}'], [$subNicheName, $countryLabel], $tpl);
}

/**
 * Génère la meta description SEO.
 */
function cs_generate_meta_description(
    string $subNicheName,
    string $language,
    string $countryLabel
): string {
    $templates = [
        'EN' => "Looking for the best {subNiche} in {country}? Our expert buying guide covers what to look for, top picks, and where to find the best deals on eBay.",
        'FR' => "À la recherche des meilleurs {subNiche} en {country} ? Notre guide expert couvre les critères de choix, les meilleures sélections et où trouver les meilleures offres sur eBay.",
        'DE' => "Die besten {subNiche} in {country} finden? Unser Expertenratgeber erklärt Kaufkriterien, Empfehlungen und die besten Angebote auf eBay.",
        'IT' => "Cerchi i migliori {subNiche} in {country}? La nostra guida esperta copre criteri di scelta, selezioni top e dove trovare le migliori offerte su eBay.",
    ];
    $tpl = $templates[$language] ?? $templates['EN'];
    $desc = str_replace(['{subNiche}', '{country}'], [$subNicheName, $countryLabel], $tpl);
    return mb_substr($desc, 0, 160);
}
