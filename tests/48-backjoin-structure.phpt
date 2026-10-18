--TEST--
Backwards join with table prefix and Discovery (bug #37)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$prefix = new NotORM\NotORM($connection, new NotORM\Structure\Convention(prefix: 'prefix_'));
echo $prefix->author("application:title", "Adminer") . "\n";
echo $prefix->author("application:application_tag:tag_id", 21) . "\n";

$discovery = new NotORM\NotORM($connection, new NotORM\Structure\Discovery($connection));
echo $discovery->author()->where("application:title", "Adminer")->fetch("name") . "\n";
?>
--EXPECTF--
SELECT prefix_author.* FROM prefix_author LEFT JOIN prefix_application AS application ON prefix_author.id = application.author_id WHERE (application.title = 'Adminer')
SELECT prefix_author.* FROM prefix_author LEFT JOIN prefix_application AS application ON prefix_author.id = application.author_id LEFT JOIN prefix_application_tag AS application_tag ON application.id = application_tag.application_id WHERE (application_tag.tag_id = 21)
Jakub Vrana
