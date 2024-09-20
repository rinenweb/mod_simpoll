CREATE TABLE IF NOT EXISTS `#__simpoll_votes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `poll_id` INT(11) NOT NULL,
    `option_text` VARCHAR(255) NOT NULL,
    `vote_count` INT(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

