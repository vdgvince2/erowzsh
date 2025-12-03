CREATE TABLE `keywords_content` (
  `keyword_id` int(11) NOT NULL,
  `part1` text NOT NULL,
  `part2` text NOT NULL,
  `part3` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `category_content` (
  `category_id` int(11) NOT NULL,
  `part1` text NOT NULL,
  `part2` text NOT NULL,
  `part3` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
