CREATE TABLE `contact_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `email` VARCHAR(254) NOT NULL,
  `message` MEDIUMTEXT NOT NULL,
  `sender_ip` VARCHAR(45) NOT NULL,             -- IPv4/IPv6 en texte
  `user_agent` VARCHAR(512) DEFAULT NULL,
  `referrer` VARCHAR(512) DEFAULT NULL,
  `submit_time_seconds` INT UNSIGNED NOT NULL,  -- delta affichage→submit
  `honeypot_hit` TINYINT(1) NOT NULL DEFAULT 0, -- 1 si rempli
  `csrf_ok` TINYINT(1) NOT NULL DEFAULT 1,      -- 1 si token OK
  `ct_allow` TINYINT(1) NOT NULL,               -- 1=clean, 0=spam selon CleanTalk
  `ct_comment` VARCHAR(255) DEFAULT NULL,       -- commentaire CleanTalk
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ct_allow` (`ct_allow`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
