-- ============================================================
-- RECOVERED SITES — Schema additionnel pour CONTENT_XX
-- Ajouter ces tables à chaque DB pays concernée.
-- ============================================================

-- ------------------------------------------------------------
-- recovered_sites : domaines récupérés via CommonCrawl
-- 1 domaine = 1 niche (configuré manuellement via admin)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recovered_sites (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain       VARCHAR(255) NOT NULL UNIQUE,       -- hostname de routing (ex: minderlist.localhost ou minderlist.com)
    crawl_domain VARCHAR(255) NOT NULL,              -- vrai domaine pour CommonCrawl (ex: minderlist.com)
    language     VARCHAR(5)   NOT NULL DEFAULT 'EN', -- EN, FR, DE, IT...
    niche_id     INT UNSIGNED DEFAULT NULL,           -- niche liée (pour maillage)
    status       ENUM('active','inactive') DEFAULT 'active',
    crawled_at   DATETIME DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (niche_id) REFERENCES niches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- recovered_pages : pages importées depuis CommonCrawl
-- old_path = chemin original (/mon-article)
-- slug      = version propre utilisée comme nouvelle URL
-- Les deux peuvent différer : old_path → 301 → slug
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recovered_pages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id       INT UNSIGNED NOT NULL,
    original_path VARCHAR(500) NOT NULL,             -- chemin brut CommonCrawl
    slug          VARCHAR(300) NOT NULL,             -- nouvelle URL propre
    title         VARCHAR(300) NOT NULL,             -- titre extrapolé de l'URL
    content_html  LONGTEXT     DEFAULT NULL,         -- contenu généré par AI
    status        ENUM('pending','generated','error') DEFAULT 'pending',
    error_msg     VARCHAR(500) DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_site_path (site_id, original_path(200)),
    UNIQUE KEY uq_site_slug (site_id, slug(200)),
    KEY idx_status (site_id, status),
    FOREIGN KEY (site_id) REFERENCES recovered_sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
