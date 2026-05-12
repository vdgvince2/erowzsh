-- ============================================================
-- CONTENT SITES — Schema SQL
-- DB unique : CONTENT — tous les sites dans une seule DB.
-- La colonne `domain` isole les données par site :
--   · En local/CLI : code pays (IE, GB, FR…)
--   · En prod      : domaine réel (antiques.ie, antiques.co.uk…)
-- Configurer les sites dans content-sites/sites.json.
-- ============================================================

-- ------------------------------------------------------------
-- niches : les 6 grandes niches (identiques pour tous les pays)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS niches (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    slug        VARCHAR(100)  NOT NULL UNIQUE,
    description TEXT,
    sort_order  TINYINT UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- sub_niches : sous-niches, chacune = un subdomain
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sub_niches (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    niche_id     INT UNSIGNED NOT NULL,
    name         VARCHAR(150) NOT NULL,
    slug         VARCHAR(150) NOT NULL,           -- e.g. antique-clocks
    ebay_query   VARCHAR(250) NOT NULL,           -- query envoyée à l'API eBay
    sort_order   TINYINT UNSIGNED DEFAULT 0,
    UNIQUE KEY uq_niche_slug (niche_id, slug),
    FOREIGN KEY (niche_id) REFERENCES niches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- articles : un article par sous-niche × domaine (généré par Claude)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS articles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sub_niche_id    INT UNSIGNED NOT NULL,
    domain          VARCHAR(100) NOT NULL,          -- clé du site (ex: IE, antiques.ie)
    language        VARCHAR(5)   NOT NULL,          -- EN, FR, DE, IT... (dérivé de sites.json)
    title           VARCHAR(300) NOT NULL,
    slug            VARCHAR(300) NOT NULL,
    meta_description VARCHAR(160),
    content_html    LONGTEXT,
    status          ENUM('draft','published','error') NOT NULL DEFAULT 'draft',
    published_at    DATETIME     DEFAULT NULL,
    indexed_at      DATETIME     DEFAULT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subniche_domain (sub_niche_id, domain),
    FOREIGN KEY (sub_niche_id) REFERENCES sub_niches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- article_products : produits eBay intégrés dans un article
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS article_products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id    INT UNSIGNED NOT NULL,
    ebay_item_id  VARCHAR(50)  NOT NULL,
    title         VARCHAR(300),
    price         DECIMAL(10,2),
    currency      VARCHAR(5)   DEFAULT 'GBP',
    image_url     VARCHAR(500),
    ebay_url      VARCHAR(1000),
    position      TINYINT UNSIGNED DEFAULT 0,     -- ordre d'apparition dans l'article
    fetched_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- sub_niche_keywords : titres pré-générés par IA (combinaisons sous-niche × angle)
-- filtrés par intention informationnelle avant génération d'article
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sub_niche_keywords (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sub_niche_id  INT UNSIGNED NOT NULL,
    domain        VARCHAR(100) NOT NULL,             -- clé du site (ex: IE, antiques.ie)
    language      VARCHAR(5)   NOT NULL,             -- EN, FR, DE, IT...
    title         VARCHAR(300) NOT NULL,
    intent_type   ENUM('informational','mixed','transactional') NOT NULL DEFAULT 'informational',
    intent_score  TINYINT UNSIGNED NOT NULL DEFAULT 0,   -- 0-100
    selected      TINYINT(1) NOT NULL DEFAULT 0,
    used          TINYINT(1) NOT NULL DEFAULT 0,         -- 1 = article déjà généré
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_kw (sub_niche_id, domain, title(200)),
    KEY idx_pending (sub_niche_id, domain, used, intent_score),
    FOREIGN KEY (sub_niche_id) REFERENCES sub_niches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- eeat_profiles : profil expert E-E-A-T par sous-niche
-- Bios stockées dans toutes les langues gérées.
-- Remplir GOOGLE_TRANSLATE_API_KEY dans inc/config.php
-- puis relancer scripts/import_eeat.php pour traduire.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS eeat_profiles (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sub_niche_id  INT UNSIGNED NOT NULL,
    expert_name   VARCHAR(200) NOT NULL,
    social_link   VARCHAR(500) NOT NULL DEFAULT '',
    bio_fr        TEXT,
    bio_en        TEXT,
    bio_de        TEXT,
    bio_it        TEXT,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_subniche (sub_niche_id),
    FOREIGN KEY (sub_niche_id) REFERENCES sub_niches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- niche_homepage_content : contenu éditorial de la homepage niche
-- 3 zones texte (Quill HTML) + image Pexels par zone
-- Une ligne par niche × domaine.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS niche_homepage_content (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    niche_id             INT UNSIGNED NOT NULL,
    domain               VARCHAR(100) NOT NULL,      -- clé du site (ex: IE, antiques.ie)
    zone1_title          VARCHAR(200)  DEFAULT NULL,
    zone1_html           LONGTEXT      DEFAULT NULL,
    zone1_pexels_keyword VARCHAR(100)  DEFAULT NULL,
    zone1_pexels_url     VARCHAR(500)  DEFAULT NULL,
    zone2_title          VARCHAR(200)  DEFAULT NULL,
    zone2_html           LONGTEXT      DEFAULT NULL,
    zone2_pexels_keyword VARCHAR(100)  DEFAULT NULL,
    zone2_pexels_url     VARCHAR(500)  DEFAULT NULL,
    zone3_title          VARCHAR(200)  DEFAULT NULL,
    zone3_html           LONGTEXT      DEFAULT NULL,
    zone3_pexels_keyword VARCHAR(100)  DEFAULT NULL,
    zone3_pexels_url     VARCHAR(500)  DEFAULT NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_niche_domain (niche_id, domain),
    FOREIGN KEY (niche_id) REFERENCES niches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- indexing_log : historique des pings d'indexation
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS indexing_log (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id  INT UNSIGNED NOT NULL,
    url         VARCHAR(500) NOT NULL,
    engine      VARCHAR(30)  DEFAULT 'google',    -- google, indexnow, pingomatic
    status      VARCHAR(50),
    response    TEXT,
    logged_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
