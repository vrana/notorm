CREATE TABLE author (
  id int NOT NULL,
  name varchar(30) NOT NULL,
  web varchar(100) NOT NULL,
  born date DEFAULT NULL,
  PRIMARY KEY (id)
);

INSERT INTO author (id, name, web, born) VALUES (11, 'Jakub Vrana', 'https://www.vrana.cz/', NULL);
INSERT INTO author (id, name, web, born) VALUES (12, 'David Grudl', 'https://davidgrudl.com/', NULL);

CREATE TABLE tag (
  id int NOT NULL,
  name varchar(20) NOT NULL,
  PRIMARY KEY (id)
);

INSERT INTO tag (id, name) VALUES (21, 'PHP');
INSERT INTO tag (id, name) VALUES (22, 'MySQL');
INSERT INTO tag (id, name) VALUES (23, 'JavaScript');

CREATE TABLE application (
  id int NOT NULL,
  author_id int NOT NULL,
  maintainer_id int,
  title varchar(50) NOT NULL,
  web varchar(100),
  slogan varchar(100) NOT NULL,
  logo blob,
  PRIMARY KEY (id),
  CONSTRAINT application_author FOREIGN KEY (author_id) REFERENCES author (id),
  CONSTRAINT application_maintainer FOREIGN KEY (maintainer_id) REFERENCES author (id)
);

CREATE INDEX application_title ON application (title);

INSERT INTO application (id, author_id, maintainer_id, title, web, slogan) VALUES (31, 11, 11, 'Adminer', 'https://www.adminer.org/', 'Database management in a single PHP file');
INSERT INTO application (id, author_id, maintainer_id, title, web, slogan) VALUES (32, 11, NULL, 'JUSH', 'https://jush.sourceforge.io/', 'JavaScript Syntax Highlighter');
INSERT INTO application (id, author_id, maintainer_id, title, web, slogan) VALUES (33, 12, 12, 'Nette', 'https://nette.org/', 'Comfortable and Safe Web Development in PHP');
INSERT INTO application (id, author_id, maintainer_id, title, web, slogan) VALUES (34, 12, 12, 'Dibi', 'https://dibi.nette.org/', 'Dibi - smart database abstraction layer');

CREATE TABLE application_tag (
  application_id int NOT NULL,
  tag_id int NOT NULL,
  PRIMARY KEY (application_id, tag_id),
  CONSTRAINT application_tag_tag FOREIGN KEY (tag_id) REFERENCES tag (id),
  CONSTRAINT application_tag_application FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE
);

INSERT INTO application_tag (application_id, tag_id) VALUES (31, 21);
INSERT INTO application_tag (application_id, tag_id) VALUES (33, 21);
INSERT INTO application_tag (application_id, tag_id) VALUES (34, 21);
INSERT INTO application_tag (application_id, tag_id) VALUES (31, 22);
INSERT INTO application_tag (application_id, tag_id) VALUES (34, 22);
INSERT INTO application_tag (application_id, tag_id) VALUES (32, 23);

CREATE TABLE category (
  id int NOT NULL,
  name varchar(30) NOT NULL,
  parent_id int,
  PRIMARY KEY (id),
  CONSTRAINT category_parent FOREIGN KEY (parent_id) REFERENCES category (id)
);

INSERT INTO category (id, name, parent_id) VALUES (41, 'Database', NULL);
INSERT INTO category (id, name, parent_id) VALUES (42, 'Web', NULL);
INSERT INTO category (id, name, parent_id) VALUES (43, 'Administration', 41);
INSERT INTO category (id, name, parent_id) VALUES (44, 'Library', 41);
INSERT INTO category (id, name, parent_id) VALUES (45, 'Framework', 42);

CREATE TABLE setting (
  site_name varchar(50) NOT NULL,
  per_page int NOT NULL
);

INSERT INTO setting (site_name, per_page) VALUES ('Software', 10);

CREATE TABLE notorm (
  id varchar(255) NOT NULL,
  data varchar(4000) NOT NULL,
  PRIMARY KEY (id)
);
