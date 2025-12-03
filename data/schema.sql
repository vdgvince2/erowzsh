CREATE TABLE IF NOT EXISTS keywords (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  keyword_name VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_keyword_name (keyword_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des annonces (20 premières par keyword côté script)
CREATE TABLE IF NOT EXISTS ads (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  keyword_id BIGINT UNSIGNED NOT NULL,
  title_original TEXT,
  photo TEXT,
  price DECIMAL(12,2) NULL,
  url TEXT,
  category_name_path TEXT,
  PRIMARY KEY (id),
  KEY idx_keyword (keyword_id),
  KEY idx_price (price),
  CONSTRAINT fk_ads_keyword
    FOREIGN KEY (keyword_id) REFERENCES keywords(id)
      ON DELETE CASCADE
      ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;