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
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `title` (`title`),
  CONSTRAINT `application_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `author` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

INSERT INTO `application` (`id`, `author_id`, `title`, `web`) VALUES
(1,	1,	'Adminer',	'http://www.adminer.org/'),
(2,	1,	'JUSH',	'http://jush.sourceforge.net/'),
(3,	2,	'Nette',	'http://nettephp.com/'),
(4,	2,	'dibi',	'http://dibiphp.com/');

CREATE TABLE `application_tag` (
  `application_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`application_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `application_tag_ibfk_3` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`),
  CONSTRAINT `application_tag_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `application` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `application_tag` (`application_id`, `tag_id`) VALUES
(1,	1),
(3,	1),
(4,	1),
(1,	2),
(4,	2),
(2,	3);

CREATE TABLE `author` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `web` varchar(100) NOT NULL,
  `born` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO `author` (`id`, `name`, `web`, `born`) VALUES
(1,	'Jakub Vrána',	'http://www.vrana.cz/',	NULL),
(2,	'David Grudl',	'http://davidgrudl.com/',	NULL);

CREATE TABLE `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO `tag` (`id`, `name`) VALUES
(1,	'PHP'),
(2,	'MySQL'),
(3,	'JavaScript');

