ALTER TABLE `#__simpoll_votes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__simpoll_votes` ADD COLUMN `answer_id` varchar(32) NOT NULL DEFAULT '' AFTER `poll_id`;
ALTER TABLE `#__simpoll_votes` ADD INDEX `idx_poll_answer` (`poll_id`, `answer_id`);
