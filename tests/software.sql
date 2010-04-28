-- Adminer 3.0.0-dev MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP DATABASE IF EXISTS `software`;
CREATE DATABASE `software` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `software`;

CREATE TABLE `application` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `web` varchar(100) NOT NULL,
  `slogan` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `title` (`title`),
  CONSTRAINT `application_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `author` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `application` (`id`, `author_id`, `title`, `web`, `slogan`) VALUES
(1,	11,	'Adminer',	'http://www.adminer.org/',	'Database management in single PHP file'),
(2,	11,	'JUSH',	'http://jush.sourceforge.net/',	'JavaScript Syntax Highlighter'),
(3,	12,	'Nette',	'http://nettephp.com/',	'Nette Framework'),
(4,	12,	'dibi',	'http://dibiphp.com/',	'tiny \'n\' smart database layer');

CREATE TABLE `application_tag` (
  `application_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`application_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `application_tag_ibfk_3` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`),
  CONSTRAINT `application_tag_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `application_tag` (`application_id`, `tag_id`) VALUES
(1,	21),
(3,	21),
(4,	21),
(1,	22),
(4,	22),
(2,	23);

CREATE TABLE `author` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `web` varchar(100) NOT NULL,
  `born` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `author` (`id`, `name`, `web`, `born`) VALUES
(11,	'Jakub Vrána',	'http://www.vrana.cz/',	NULL),
(12,	'David Grudl',	'http://davidgrudl.com/',	NULL);

CREATE TABLE `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tag` (`id`, `name`) VALUES
(21,	'PHP'),
(22,	'MySQL'),
(23,	'JavaScript');

