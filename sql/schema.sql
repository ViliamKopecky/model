SET NAMES utf8;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` varchar(40) NOT NULL,
  `type` enum('post','page','movie') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `props`;
CREATE TABLE `props` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prop_type` enum('title','created_at','content','meta') NOT NULL,
  `post_id` varchar(40) NOT NULL,
  `string_value` varchar(128) DEFAULT NULL,
  `text_value` text,
  `int_value` int(11) DEFAULT NULL,
  `float_value` float DEFAULT NULL,
  `bool_value` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_uid` (`post_id`),
  CONSTRAINT `props_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `links`;
CREATE TABLE `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `link_type` enum('related') NOT NULL,
  `linker_id` varchar(40) NOT NULL,
  `linked_id` varchar(40) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `linker_id` (`linker_id`),
  KEY `linked_id` (`linked_id`),
  CONSTRAINT `links_ibfk_1` FOREIGN KEY (`linker_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `links_ibfk_2` FOREIGN KEY (`linked_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS=1;