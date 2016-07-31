-- Adminer 4.2.2 SQLite 3 dump

DROP TABLE IF EXISTS "application";
CREATE TABLE application (
  id int NOT NULL,
  author_id int NOT NULL,
  maintainer_id int,
  title varchar(50) NOT NULL,
  web varchar(100),
  slogan varchar(100) NOT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX "application_title" ON "application" ("title");

INSERT INTO "application" ("id", "author_id", "maintainer_id", "title", "web", "slogan") VALUES (1,	11,	11,	'Adminer',	'http://www.adminer.org/',	'Database management in single PHP file');
INSERT INTO "application" ("id", "author_id", "maintainer_id", "title", "web", "slogan") VALUES (2,	11,	NULL,	'JUSH',	'http://jush.sourceforge.net/',	'JavaScript Syntax Highlighter');
INSERT INTO "application" ("id", "author_id", "maintainer_id", "title", "web", "slogan") VALUES (3,	12,	12,	'Nette',	'http://nettephp.com/',	'Nette Framework for PHP 5');
INSERT INTO "application" ("id", "author_id", "maintainer_id", "title", "web", "slogan") VALUES (4,	12,	12,	'Dibi',	'http://dibiphp.com/',	'Database Abstraction Library for PHP 5');

DROP TABLE IF EXISTS "application_tag";
CREATE TABLE application_tag (
  application_id int NOT NULL,
  tag_id int NOT NULL,
  PRIMARY KEY (application_id, tag_id)
);

INSERT INTO "application_tag" ("application_id", "tag_id") VALUES (1,	21);
INSERT INTO "application_tag" ("application_id", "tag_id") VALUES (3,	21);
INSERT INTO "application_tag" ("application_id", "tag_id") VALUES (4,	21);
INSERT INTO "application_tag" ("application_id", "tag_id") VALUES (1,	22);
INSERT INTO "application_tag" ("application_id", "tag_id") VALUES (4,	22);
INSERT INTO "application_tag" ("application_id", "tag_id") VALUES (2,	23);

DROP TABLE IF EXISTS "author";
CREATE TABLE author (
  id int NOT NULL,
  name varchar(30) NOT NULL,
  web varchar(100) NOT NULL,
  born date DEFAULT NULL,
  PRIMARY KEY (id)
);

INSERT INTO "author" ("id", "name", "web", "born") VALUES (11,	'Jakub Vrana',	'http://www.vrana.cz/',	NULL);
INSERT INTO "author" ("id", "name", "web", "born") VALUES (12,	'David Grudl',	'http://davidgrudl.com/',	NULL);

DROP TABLE IF EXISTS "tag";
CREATE TABLE tag (
  id int NOT NULL,
  name varchar(20) NOT NULL,
  PRIMARY KEY (id)
);

INSERT INTO "tag" ("id", "name") VALUES (21,	'PHP');
INSERT INTO "tag" ("id", "name") VALUES (22,	'MySQL');
INSERT INTO "tag" ("id", "name") VALUES (23,	'JavaScript');

--