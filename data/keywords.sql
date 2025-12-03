ALTER TABLE `keywords` ADD `keywordURL` VARCHAR(1024) NOT NULL AFTER `keyword_name`;
ALTER TABLE `keywords` ADD `homepage` BOOLEAN NOT NULL DEFAULT FALSE AFTER `keywordURL`;



/* index sur les ads */
CREATE INDEX idx_ads_keyword ON ads(keyword_id);
CREATE INDEX idx_ads_cat1 ON ads(category_level1);
CREATE INDEX idx_ads_cat2 ON ads(category_level2);
CREATE INDEX idx_ads_cat3 ON ads(category_level3);

/* préparer pour ajouter la catégorie */
ALTER TABLE `keywords` ADD `main_category` INT(11) NOT NULL DEFAULT '0' AFTER `homepage`;

/* Delete the orphans keywords wrongly imported from the mongo export */
DELETE k
FROM keywords k
LEFT JOIN ads a ON a.keyword_id = k.id
WHERE a.keyword_id IS NULL;

ALTER TABLE `keywords` ADD `last_visited` DATETIME NULL DEFAULT NULL AFTER `main_category`;

/* keyword crawler */
ALTER TABLE `keywords` ADD `last_update` DATETIME NULL DEFAULT NULL AFTER `last_visited`;
ALTER TABLE `ads` ADD `description_itemspecs` VARCHAR(512) NULL DEFAULT NULL AFTER `title_original`;
ALTER TABLE `ads` ADD `insert_date` DATETIME NULL DEFAULT NULL AFTER `category_level3`;