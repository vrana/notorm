-- Converted by db_converter
SET standard_conforming_strings=off;
SET escape_string_warning=off;
SET CONSTRAINTS ALL DEFERRED;

START TRANSACTION;

CREATE TABLE IF NOT EXISTS "application" (
    "id" integer NOT NULL,
    "author_id" integer NOT NULL,
    "maintainer_id" integer DEFAULT NULL,
    "title" varchar(100) NOT NULL,
    "web" varchar(200) DEFAULT NULL,
    "slogan" varchar(200) NOT NULL,
    PRIMARY KEY ("id")
);

INSERT INTO "application" ( id, author_id, maintainer_id, title, web, slogan )
  (
    SELECT 1,11,11,'Adminer','http://www.adminer.org/','Database management in single PHP file'
      WHERE NOT EXISTS (SELECT 1 FROM "application" WHERE id = 1)
  );

INSERT INTO "application" ( id, author_id, maintainer_id, title, web, slogan )
  (
    SELECT 2,11,NULL,'JUSH','http://jush.sourceforge.net/','JavaScript Syntax Highlighter'
    WHERE NOT EXISTS (SELECT 1 FROM "application" WHERE id = 2)
  );

INSERT INTO "application" ( id, author_id, maintainer_id, title, web, slogan )
  (
    SELECT 3,12,12,'Nette','http://nettephp.com/','Nette Framework for PHP 5'
    WHERE NOT EXISTS (SELECT 1 FROM "application" WHERE id = 3)
  );

INSERT INTO "application" ( id, author_id, maintainer_id, title, web, slogan )
  (
    SELECT 4,12,12,'Dibi','http://dibiphp.com/','Database Abstraction Library for PHP 5'
    WHERE NOT EXISTS (SELECT 1 FROM "application" WHERE id = 4)
  );

CREATE TABLE IF NOT EXISTS "application_tag" (
    "application_id" integer NOT NULL,
    "tag_id" integer NOT NULL,
    PRIMARY KEY ("application_id","tag_id")
);

INSERT INTO "application_tag" ( application_id, tag_id )
  (SELECT 1,21 WHERE NOT EXISTS (SELECT 1 FROM "application_tag" WHERE application_id = 1 AND tag_id = 21));

INSERT INTO "application_tag" ( application_id, tag_id )
  (SELECT 3,21 WHERE NOT EXISTS (SELECT 1 FROM "application_tag" WHERE application_id = 3 AND tag_id = 21));

INSERT INTO "application_tag" ( application_id, tag_id )
  (SELECT 4,21 WHERE NOT EXISTS (SELECT 1 FROM "application_tag" WHERE application_id = 4 AND tag_id = 21));

INSERT INTO "application_tag" ( application_id, tag_id )
  (SELECT 1,22 WHERE NOT EXISTS (SELECT 1 FROM "application_tag" WHERE application_id = 1 AND tag_id = 22));

INSERT INTO "application_tag" ( application_id, tag_id )
  (SELECT 4,22 WHERE NOT EXISTS (SELECT 1 FROM "application_tag" WHERE application_id = 4 AND tag_id = 22));

INSERT INTO "application_tag" ( application_id, tag_id )
  (SELECT 2,23 WHERE NOT EXISTS (SELECT 1 FROM "application_tag" WHERE application_id = 2 AND tag_id = 23));

CREATE TABLE IF NOT EXISTS "author" (
    "id" integer NOT NULL,
    "name" varchar(60) NOT NULL,
    "web" varchar(200) NOT NULL,
    "born" date DEFAULT NULL,
    PRIMARY KEY ("id")
);

INSERT INTO "author" ( id, name, web, born )
  ( SELECT 11,'Jakub Vrana','http://www.vrana.cz/',NULL
    WHERE NOT EXISTS (SELECT 1 FROM "author" WHERE id = 11 )
  );

INSERT INTO "author" ( id, name, web, born )
  ( SELECT 12,'David Grudl','http://davidgrudl.com/',NULL
      WHERE NOT EXISTS (SELECT 1 FROM "author" WHERE id = 12 )
  );

CREATE TABLE IF NOT EXISTS "tag" (
    "id" integer NOT NULL,
    "name" varchar(40) NOT NULL,
    PRIMARY KEY ("id")
);

INSERT INTO "tag" ( id, name )
  ( SELECT 21,'PHP'
    WHERE NOT EXISTS (SELECT 1 FROM "tag" WHERE id = 21 )
  );

INSERT INTO "tag" ( id, name )
  ( SELECT 22,'MySQL'
    WHERE NOT EXISTS (SELECT 1 FROM "tag" WHERE id = 22 )
  );

INSERT INTO "tag" ( id, name )
  ( SELECT 23,'JavaScript'
    WHERE NOT EXISTS (SELECT 1 FROM "tag" WHERE id = 23 )
  );

-- Foreign keys --

ALTER TABLE "application" DROP CONSTRAINT IF EXISTS "application_author";
ALTER TABLE "application" ADD CONSTRAINT "application_author" FOREIGN KEY ("author_id") REFERENCES "author" ("id") DEFERRABLE INITIALLY DEFERRED;
DROP INDEX IF EXISTS "author_id";
CREATE INDEX ON "application" ("author_id");

ALTER TABLE "application" DROP CONSTRAINT IF EXISTS "application_maintainer";
ALTER TABLE "application" ADD CONSTRAINT "application_maintainer" FOREIGN KEY ("maintainer_id") REFERENCES "author" ("id") DEFERRABLE INITIALLY DEFERRED;
DROP INDEX IF EXISTS "maintainer_id";
CREATE INDEX ON "application" ("maintainer_id");

ALTER TABLE "application_tag" DROP CONSTRAINT IF EXISTS "application_tag_application";
ALTER TABLE "application_tag" ADD CONSTRAINT "application_tag_application" FOREIGN KEY ("application_id") REFERENCES "application" ("id") ON DELETE CASCADE DEFERRABLE INITIALLY DEFERRED;
DROP INDEX IF EXISTS "application_id" CASCADE;
CREATE INDEX ON "application_tag" ("application_id");

ALTER TABLE "application_tag" DROP CONSTRAINT IF EXISTS "application_tag_tag";
ALTER TABLE "application_tag" ADD CONSTRAINT "application_tag_tag" FOREIGN KEY ("tag_id") REFERENCES "tag" ("id") DEFERRABLE INITIALLY DEFERRED;
DROP INDEX IF EXISTS "tag_id";
CREATE INDEX ON "application_tag" ("tag_id");

-- -- Sequences --
DROP SEQUENCE IF EXISTS application_id_seq CASCADE;
CREATE SEQUENCE application_id_seq;
SELECT setval('application_id_seq', max(id)) FROM application;
ALTER TABLE "application" ALTER COLUMN "id" SET DEFAULT nextval('application_id_seq');

DROP SEQUENCE IF EXISTS author_id_seq CASCADE;
CREATE SEQUENCE author_id_seq;
SELECT setval('author_id_seq', max(id)) FROM author;
ALTER TABLE "author" ALTER COLUMN "id" SET DEFAULT nextval('author_id_seq');

DROP SEQUENCE IF EXISTS tag_id_seq CASCADE;
CREATE SEQUENCE tag_id_seq;
SELECT setval('tag_id_seq', max(id)) FROM tag;
ALTER TABLE "tag" ALTER COLUMN "id" SET DEFAULT nextval('tag_id_seq');

COMMIT;
