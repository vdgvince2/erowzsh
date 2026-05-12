<?php
/**
 * Content-Sites — Gestion des articles en DB + rendu HTML
 *
 * Toutes les fonctions de requête filtrent par $domain (clé DB du site courant).
 * En local/CLI : $domain = code pays (IE, GB…)
 * En prod      : $domain = domaine réel (antiques.ie)
 */

// ── DB helpers ────────────────────────────────────────────────────────────────

/**
 * Récupère la prochaine sous-niche sans article pour ce domaine.
 *
 * Priorités :
 *  1. Sous-niches avec un keyword disponible (non utilisé, meilleur score)
 *  2. Sous-niches sans aucun article ni keyword (fallback template)
 *  3. Sous-niches en erreur (retry)
 */
function cs_next_pending_subniche(PDO $pdo, string $domain): ?array
{
    // 1. Keyword disponible, pas encore d'article
    $stmt = $pdo->prepare('
        SELECT sn.*, n.name AS niche_name, n.slug AS niche_slug,
               kw.id AS kw_id, kw.title AS kw_title
        FROM sub_niches sn
        JOIN niches n ON n.id = sn.niche_id
        JOIN sub_niche_keywords kw
            ON kw.id = (
                SELECT id FROM sub_niche_keywords
                WHERE sub_niche_id = sn.id AND domain = :dom AND used = 0
                ORDER BY intent_score DESC
                LIMIT 1
            )
        WHERE sn.id NOT IN (
            SELECT sub_niche_id FROM articles WHERE domain = :dom2
        )
        ORDER BY n.sort_order ASC, sn.sort_order ASC
        LIMIT 1
    ');
    $stmt->execute([':dom' => $domain, ':dom2' => $domain]);
    $row = $stmt->fetch();
    if ($row) return $row;

    // 2. Fallback : aucun article, aucun keyword
    $stmt = $pdo->prepare('
        SELECT sn.*, n.name AS niche_name, n.slug AS niche_slug,
               NULL AS kw_id, NULL AS kw_title
        FROM sub_niches sn
        JOIN niches n ON n.id = sn.niche_id
        WHERE sn.id NOT IN (
            SELECT sub_niche_id FROM articles WHERE domain = :dom
        )
        ORDER BY n.sort_order ASC, sn.sort_order ASC
        LIMIT 1
    ');
    $stmt->execute([':dom' => $domain]);
    $row = $stmt->fetch();
    if ($row) return $row;

    // 3. Fallback : articles en erreur (retry)
    $stmt = $pdo->prepare('
        SELECT sn.*, n.name AS niche_name, n.slug AS niche_slug,
               kw.id AS kw_id, kw.title AS kw_title
        FROM sub_niches sn
        JOIN niches n ON n.id = sn.niche_id
        JOIN articles a ON a.sub_niche_id = sn.id AND a.domain = :dom
        LEFT JOIN sub_niche_keywords kw
            ON kw.id = (
                SELECT id FROM sub_niche_keywords
                WHERE sub_niche_id = sn.id AND domain = :dom2 AND used = 0
                ORDER BY intent_score DESC
                LIMIT 1
            )
        WHERE a.status = "error"
        ORDER BY a.updated_at ASC
        LIMIT 1
    ');
    $stmt->execute([':dom' => $domain, ':dom2' => $domain]);
    return $stmt->fetch() ?: null;
}

/**
 * Insère ou met à jour un article en DB.
 * La clé unique est (sub_niche_id, domain).
 */
function cs_upsert_article(
    PDO    $pdo,
    int    $subNicheId,
    string $domain,
    string $language,
    string $title,
    string $slug,
    string $metaDescription,
    string $contentHtml,
    string $status = 'draft'
): int {
    $stmt = $pdo->prepare('
        INSERT INTO articles
            (sub_niche_id, domain, language, title, slug, meta_description, content_html, status)
        VALUES
            (:sub_niche_id, :domain, :language, :title, :slug, :meta, :content, :status)
        ON DUPLICATE KEY UPDATE
            title            = VALUES(title),
            slug             = VALUES(slug),
            meta_description = VALUES(meta_description),
            content_html     = VALUES(content_html),
            status           = VALUES(status),
            updated_at       = NOW()
    ');
    $stmt->execute([
        ':sub_niche_id' => $subNicheId,
        ':domain'       => $domain,
        ':language'     => $language,
        ':title'        => $title,
        ':slug'         => $slug,
        ':meta'         => $metaDescription,
        ':content'      => $contentHtml,
        ':status'       => $status,
    ]);

    if ($pdo->lastInsertId()) {
        return (int) $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('SELECT id FROM articles WHERE sub_niche_id = :sn AND domain = :dom LIMIT 1');
    $stmt->execute([':sn' => $subNicheId, ':dom' => $domain]);
    return (int) $stmt->fetchColumn();
}

/**
 * Passe un article en statut "published".
 */
function cs_publish_article(PDO $pdo, int $articleId): void
{
    $pdo->prepare('UPDATE articles SET status = "published", published_at = NOW() WHERE id = :id')
        ->execute([':id' => $articleId]);
}

/**
 * Marque un article comme indexé.
 */
function cs_mark_indexed(PDO $pdo, int $articleId): void
{
    $pdo->prepare('UPDATE articles SET indexed_at = NOW() WHERE id = :id')
        ->execute([':id' => $articleId]);
}

/**
 * Enregistre un ping d'indexation dans le log.
 */
function cs_log_indexing(PDO $pdo, int $articleId, string $url, string $engine, string $status, string $response = ''): void
{
    $pdo->prepare('
        INSERT INTO indexing_log (article_id, url, engine, status, response)
        VALUES (:aid, :url, :engine, :status, :response)
    ')->execute([
        ':aid'      => $articleId,
        ':url'      => $url,
        ':engine'   => $engine,
        ':status'   => $status,
        ':response' => mb_substr($response, 0, 2000),
    ]);
}

/**
 * Récupère un article publié par slug (dans une sous-niche donnée).
 */
function cs_get_article_by_slug(PDO $pdo, string $nicheSlug, string $subNicheSlug, string $domain, ?string $articleSlug = null): ?array
{
    $sql = '
        SELECT a.*, sn.name AS sub_niche_name, sn.slug AS sub_niche_slug,
               n.name AS niche_name, n.slug AS niche_slug,
               (SELECT ap.image_url FROM article_products ap
                WHERE ap.article_id = a.id ORDER BY ap.position ASC LIMIT 1
               ) AS cover_image_url
        FROM articles a
        JOIN sub_niches sn ON sn.id = a.sub_niche_id
        JOIN niches n ON n.id = sn.niche_id
        WHERE n.slug = :niche AND sn.slug = :subniche
          AND a.domain = :domain AND a.status = "published"
    ';
    $params = [':niche' => $nicheSlug, ':subniche' => $subNicheSlug, ':domain' => $domain];

    if ($articleSlug !== null) {
        $sql .= ' AND a.slug = :article_slug';
        $params[':article_slug'] = $articleSlug;
    } else {
        $sql .= ' ORDER BY a.published_at DESC';
    }
    $sql .= ' LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: null;
}

/**
 * Récupère tous les articles publiés d'une niche pour ce domaine.
 */
function cs_get_articles_for_niche(PDO $pdo, string $nicheSlug, string $domain, int $limit = 24): array
{
    $stmt = $pdo->prepare('
        SELECT a.id, a.title, a.slug, a.meta_description, a.published_at,
               sn.name AS sub_niche_name, sn.slug AS sub_niche_slug,
               n.name AS niche_name, n.slug AS niche_slug,
               (SELECT ap.image_url FROM article_products ap
                WHERE ap.article_id = a.id ORDER BY ap.position ASC LIMIT 1
               ) AS cover_image_url
        FROM articles a
        JOIN sub_niches sn ON sn.id = a.sub_niche_id
        JOIN niches n ON n.id = sn.niche_id
        WHERE n.slug = :niche AND a.domain = :domain AND a.status = "published"
        ORDER BY a.published_at DESC
        LIMIT :lim
    ');
    $stmt->bindValue(':niche',  $nicheSlug);
    $stmt->bindValue(':domain', $domain);
    $stmt->bindValue(':lim',    $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère tous les articles publiés d'une sous-niche pour ce domaine.
 */
function cs_get_articles_for_subniche(PDO $pdo, int $subNicheId, string $domain, int $limit = 20, ?int $excludeId = null): array
{
    $sql = '
        SELECT a.id, a.title, a.slug, a.meta_description, a.published_at,
               (SELECT ap.image_url FROM article_products ap
                WHERE ap.article_id = a.id ORDER BY ap.position ASC LIMIT 1
               ) AS cover_image_url
        FROM articles a
        WHERE a.sub_niche_id = :sn AND a.domain = :domain AND a.status = "published"
    ';
    $params = [':sn' => $subNicheId, ':domain' => $domain];

    if ($excludeId !== null) {
        $sql .= ' AND a.id != :excl';
        $params[':excl'] = $excludeId;
    }
    $sql .= ' ORDER BY a.published_at DESC LIMIT ' . (int)$limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Récupère toutes les sous-niches d'une niche (nav, pas de filtre domaine).
 */
function cs_get_subniche_nav(PDO $pdo, string $nicheSlug): array
{
    $stmt = $pdo->prepare('
        SELECT sn.id, sn.name, sn.slug
        FROM sub_niches sn
        JOIN niches n ON n.id = sn.niche_id
        WHERE n.slug = :slug
        ORDER BY sn.sort_order ASC, sn.name ASC
    ');
    $stmt->execute([':slug' => $nicheSlug]);
    return $stmt->fetchAll();
}

/**
 * Récupère toutes les sous-niches d'une niche avec leur image de couverture
 * pour ce domaine.
 */
function cs_get_subniche_list(PDO $pdo, string $nicheSlug, string $domain): array
{
    $stmt = $pdo->prepare('
        SELECT sn.*,
               n.name AS niche_name,
               a.title AS article_title,
               a.status AS article_status,
               (SELECT ap.image_url FROM article_products ap
                WHERE ap.article_id = a.id ORDER BY ap.position ASC LIMIT 1
               ) AS cover_image_url
        FROM sub_niches sn
        JOIN niches n ON n.id = sn.niche_id
        LEFT JOIN articles a ON a.sub_niche_id = sn.id AND a.domain = :domain AND a.status = "published"
        WHERE n.slug = :slug
        ORDER BY sn.sort_order ASC, sn.name ASC
    ');
    $stmt->execute([':slug' => $nicheSlug, ':domain' => $domain]);
    return $stmt->fetchAll();
}

// ── Keywords ──────────────────────────────────────────────────────────────────

/**
 * Insère une liste de keywords pour une sous-niche + domaine.
 */
function cs_save_keywords(PDO $pdo, int $subNicheId, string $domain, string $language, array $keywords): void
{
    $insert = $pdo->prepare('
        INSERT IGNORE INTO sub_niche_keywords
            (sub_niche_id, domain, language, title, intent_type, intent_score)
        VALUES
            (:sn, :domain, :lang, :title, :type, :score)
    ');

    foreach ($keywords as $kw) {
        $insert->execute([
            ':sn'     => $subNicheId,
            ':domain' => $domain,
            ':lang'   => $language,
            ':title'  => $kw['title'],
            ':type'   => $kw['intent'] ?? 'informational',
            ':score'  => (int) ($kw['score'] ?? 80),
        ]);
    }
}

/**
 * Marque un keyword comme utilisé.
 */
function cs_mark_keyword_used(PDO $pdo, int $keywordId): void
{
    $pdo->prepare('UPDATE sub_niche_keywords SET used = 1 WHERE id = :id')
        ->execute([':id' => $keywordId]);
}

/**
 * Retourne true si des keywords existent déjà pour cette sous-niche + domaine.
 */
function cs_keywords_exist(PDO $pdo, int $subNicheId, string $domain): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*) FROM sub_niche_keywords
        WHERE sub_niche_id = :sn AND domain = :domain
    ');
    $stmt->execute([':sn' => $subNicheId, ':domain' => $domain]);
    return (int) $stmt->fetchColumn() > 0;
}

// ── Rendu ─────────────────────────────────────────────────────────────────────

/**
 * Injecte les blocs produits eBay dans le HTML.
 * Remplace <!-- PRODUCT_BLOCK_1 -->, _2, _3 par des grilles.
 */
function cs_inject_product_blocks(string $html, array $products, string $currency = '£'): string
{
    $chunks = array_chunk($products, (int) ceil(count($products) / 3));

    for ($i = 1; $i <= 3; $i++) {
        $block   = $chunks[$i - 1] ?? [];
        $gridHtml = cs_render_product_grid($block, $currency);
        $html     = str_replace("<!-- PRODUCT_BLOCK_{$i} -->", $gridHtml, $html);
    }

    return $html;
}

/**
 * Rendu HTML d'une grille de produits eBay.
 */
function cs_render_product_grid(array $products, string $currency = '£'): string
{
    if (empty($products)) return '';

    $items = '';
    foreach ($products as $p) {
        $title    = htmlspecialchars($p['title'],     ENT_QUOTES, 'UTF-8');
        $imgUrl   = htmlspecialchars($p['image_url'], ENT_QUOTES, 'UTF-8');
        $ebayUrl  = htmlspecialchars($p['ebay_url'],  ENT_QUOTES, 'UTF-8');
        $price    = number_format((float)$p['price'], 2);
        $cur      = htmlspecialchars($p['currency'] ?? $currency, ENT_QUOTES, 'UTF-8');

        $items .= <<<HTML
        <div class="cs-product-card">
            <a href="{$ebayUrl}" target="_blank" rel="nofollow noopener sponsored" class="cs-product-link">
                <div class="cs-product-img-wrap">
                    <img src="{$imgUrl}" alt="{$title}" loading="lazy" width="220" height="220">
                </div>
                <div class="cs-product-info">
                    <p class="cs-product-title">{$title}</p>
                    <p class="cs-product-price">{$cur}{$price}</p>
                    <span class="cs-product-cta">View on eBay →</span>
                </div>
            </a>
        </div>
        HTML;
    }

    return '<div class="cs-product-grid">' . $items . '</div>';
}

/**
 * Génère le slug URL d'un article à partir du titre.
 */
function cs_slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return trim($text, '-');
}

/**
 * Retourne l'URL publique d'un article.
 */
function cs_article_url(string $subNicheSlug, string $nicheSlug, string $rootDomain): string
{
    return $rootDomain . '/';
}
