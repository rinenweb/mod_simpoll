CREATE TABLE IF NOT EXISTS `#__simpoll_votes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` int unsigned NOT NULL,
  `answer_id` varchar(32) NOT NULL DEFAULT '',
  `option_text` varchar(255) NOT NULL DEFAULT '',
  `vote_count` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_poll_answer` (`poll_id`, `answer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
