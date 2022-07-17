#
# SQL Export
# Created by Querious (301010)
# Created: 17. July 2022 at 19:56:48 CEST
# Encoding: Unicode (UTF-8)
#


SET @ORIG_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

SET @ORIG_UNIQUE_CHECKS = @@UNIQUE_CHECKS;
SET UNIQUE_CHECKS = 0;

SET @ORIG_TIME_ZONE = @@TIME_ZONE;
SET TIME_ZONE = '+00:00';

SET @ORIG_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';



DROP TABLE IF EXISTS `titles_genres`;
DROP TABLE IF EXISTS `titles`;
DROP TABLE IF EXISTS `titleakatypes`;
DROP TABLE IF EXISTS `titleakas_titleakatypes`;
DROP TABLE IF EXISTS `titleakas_titleakaattributes`;
DROP TABLE IF EXISTS `titleakas`;
DROP TABLE IF EXISTS `titleakaattributes`;
DROP TABLE IF EXISTS `professions`;
DROP TABLE IF EXISTS `principals_characters`;
DROP TABLE IF EXISTS `principals`;
DROP TABLE IF EXISTS `names_primaryprofessions`;
DROP TABLE IF EXISTS `names_knownfortitles`;
DROP TABLE IF EXISTS `names`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `genres`;
DROP TABLE IF EXISTS `episodes`;
DROP TABLE IF EXISTS `categories`;


CREATE TABLE `categories` (
  `code` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `episodes` (
  `id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `season_number` int DEFAULT NULL,
  `episode_number` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `genres` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `id_index` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `jobs` (
  `code` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `names` (
  `id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `primary_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `birth_year` int unsigned DEFAULT NULL,
  `death_year` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `birth_year_index` (`birth_year`) USING BTREE,
  KEY `death_year_index` (`death_year`) USING BTREE,
  FULLTEXT KEY `primary_name_index` (`primary_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `names_knownfortitles` (
  `name_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`name_id`,`title_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `names_primaryprofessions` (
  `name_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `profession_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`name_id`,`profession_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `principals` (
  `id` int NOT NULL,
  `title_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordering` int unsigned DEFAULT NULL,
  `category_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `principals_characters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `principal_id` int NOT NULL,
  `character_display_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25742402 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `professions` (
  `id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `titleakaattributes` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `titleakas` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ordering` int DEFAULT NULL,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `region` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_original_title` tinyint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `titleakas_titleakaattributes` (
  `titleaka_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titleakaattribute_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`titleaka_id`,`titleakaattribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `titleakas_titleakatypes` (
  `titleaka_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titleakatype_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`titleaka_id`,`titleakatype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `titleakatypes` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `titles` (
  `id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_type` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `original_title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_adult` tinyint unsigned DEFAULT NULL,
  `start_year` int unsigned DEFAULT NULL,
  `end_year` int unsigned DEFAULT NULL,
  `runtime_minutes` int unsigned DEFAULT NULL,
  `average_rating` float DEFAULT NULL,
  `num_votes` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `average_rating_index` (`average_rating`) USING BTREE,
  KEY `num_votes_index` (`num_votes`) USING BTREE,
  KEY `start_year_index` (`start_year`) USING BTREE,
  KEY `runtime_minutes_index` (`runtime_minutes`) USING BTREE,
  FULLTEXT KEY `primary_title_index` (`primary_title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `titles_genres` (
  `title_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `genre_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`title_id`,`genre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;






SET FOREIGN_KEY_CHECKS = @ORIG_FOREIGN_KEY_CHECKS;

SET UNIQUE_CHECKS = @ORIG_UNIQUE_CHECKS;

SET @ORIG_TIME_ZONE = @@TIME_ZONE;
SET TIME_ZONE = @ORIG_TIME_ZONE;

SET SQL_MODE = @ORIG_SQL_MODE;



# Export Finished: 17. July 2022 at 19:56:48 CEST

