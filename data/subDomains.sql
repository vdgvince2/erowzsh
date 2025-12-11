
CREATE TABLE `subdomain_ads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `keyword_id` bigint(20) UNSIGNED NOT NULL,
  `title_original` text,
  `description_itemspecs` varchar(512) DEFAULT NULL,
  `photo` text,
  `price` decimal(12,2) DEFAULT NULL,
  `url` text,
  `insert_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `subdomain_keywords`
--

CREATE TABLE `subdomain_keywords` (
  `id` int(11) NOT NULL,
  `keyword_name` varchar(128) NOT NULL,
  `subdomain` varchar(128) NOT NULL,
  `last_update` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `subdomain_ads`
--
ALTER TABLE `subdomain_ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_keyword` (`keyword_id`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_ads_keyword` (`keyword_id`);

--
-- Indexes for table `subdomain_keywords`
--
ALTER TABLE `subdomain_keywords`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `subdomain_ads`
--
ALTER TABLE `subdomain_ads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subdomain_keywords`
--
ALTER TABLE `subdomain_keywords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

ALTER TABLE `subdomain_keywords` ADD FULLTEXT(`keyword_name`);