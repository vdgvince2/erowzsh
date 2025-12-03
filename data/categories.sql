
/* ajouter les champs*/
ALTER TABLE ads
ADD COLUMN category_level1 VARCHAR(255) AFTER category_name_path,
ADD COLUMN category_level2 VARCHAR(255) AFTER category_level1,
ADD COLUMN category_level3 VARCHAR(255) AFTER category_level2;


/* créer la table categories*/
CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `categories` ADD `level` INT(4) NOT NULL DEFAULT '0' AFTER `url`;
ALTER TABLE `categories` ADD `parentid` INT(4) NULL DEFAULT '0' AFTER `level`;


/* préparation slug multi level */
ALTER TABLE categories
  ADD COLUMN slug_path VARCHAR(255) NOT NULL DEFAULT '' AFTER url;

/* homepage visibility */
ALTER TABLE `categories` ADD `homepage` BOOLEAN NULL DEFAULT FALSE AFTER `parentid`;

/* assign the other category . We give "ebay" name to be language agnostic */
INSERT INTO `categories` (`id`, `name`, `url`, `slug_path`, `level`, `parentid`, `homepage`) VALUES (NULL, 'eBay', 'eBay', '/ebay', '1', '1', '1');

ALTER TABLE `categories` ADD FULLTEXT(`name`);