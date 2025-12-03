CREATE TABLE `notfound` (
  `id` bigint(20) NOT NULL,
  `keywordname` varchar(1024) DEFAULT NULL,
  `last_detected` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `notfound`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `notfound`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;
COMMIT;

ALTER TABLE `notfound` ADD UNIQUE(`keywordname`);

ALTER TABLE `notfound` ADD `noads` BOOLEAN NOT NULL DEFAULT FALSE AFTER `last_detected`;