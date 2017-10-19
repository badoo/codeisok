create database IF NOT EXISTS gitphp;
use gitphp;

CREATE TABLE IF NOT EXISTS `Comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `snapshot_id` int(10) unsigned NOT NULL,
  `author` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `file` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `line` int(10) unsigned DEFAULT NULL,
  `text` text COLLATE utf8_unicode_ci,
  `line_start` int(10) unsigned DEFAULT NULL,
  `diff_data` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('Draft','Finish','Deleted') COLLATE utf8_unicode_ci NOT NULL,
  `real_line` int(10) unsigned DEFAULT NULL,
  `real_line_start` int(10) unsigned DEFAULT NULL,
  `real_line_before` int(10) unsigned DEFAULT NULL,
  `real_line_before_start` int(10) unsigned DEFAULT NULL,
  `lines_count` int(10) unsigned DEFAULT '0',
  `side` enum('lhs','rhs') COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `Heads` (
  `branch` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hash` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `Review` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `Snapshot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_id` int(10) unsigned NOT NULL,
  `hash_head` varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `hash_base` varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `repo` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `review_type` enum('unified','sidebyside') COLLATE utf8_bin NOT NULL DEFAULT 'unified',
  PRIMARY KEY (`id`),
  KEY `review_id` (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `Access` (
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `repository_id` int(11) unsigned NOT NULL DEFAULT '0',
  `mode` enum('readonly','writable') NOT NULL DEFAULT 'readonly',
  UNIQUE KEY `user_repository` (`user_id`,`repository_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `Repository` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project` varchar(128) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `category` varchar(64) DEFAULT NULL,
  `notify_email` varchar(255) NOT NULL DEFAULT '',
  `restricted` enum('No','Yes') NOT NULL DEFAULT 'No',
  `display` enum('No','Yes') NOT NULL DEFAULT 'No',
  `owner` varchar(255) NOT NULL DEFAULT '',
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project` (`project`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `User` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(128) NOT NULL,
  `email` varchar(256) NOT NULL DEFAULT '',
  `public_key` text NOT NULL,
  `access_mode` enum('normal','everywhere') NOT NULL DEFAULT 'normal',
  `comment` text,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
