<?php declare(strict_types=1); ?>
--TEST--
Backwards join to the same table
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$discovery = new NotORM\NotORM($connection, new NotORM\Structure\Discovery($connection, foreign: '%s_id'));

$categories = $discovery->category("category:name", "Library");
echo "$categories\n";
foreach ($categories as $category) {
	echo "$category[name]\n";
}
echo "\n";

$categories = $discovery->category()->select("category.name, category:name AS child")->where("category:id IS NOT NULL")->order("category:name");
echo "$categories\n";
foreach ($categories as $category) {
	echo "$category[name]: $category[child]\n";
}
echo "\n";

foreach ($discovery->category("parent_id IS NOT NULL")->order("id") as $category) {
	echo "$category[name] (" . $category->parent["name"] . ")\n";
}
?>
--EXPECTF--
SELECT category.* FROM category LEFT JOIN category category2 ON category.id = category2.parent_id WHERE (category2.name = 'Library')
Database

SELECT category.name, category2.name AS child FROM category LEFT JOIN category category2 ON category.id = category2.parent_id WHERE (category2.id IS NOT NULL) ORDER BY category2.name
Database: Administration
Web: Framework
Database: Library

Administration (Database)
Library (Database)
Framework (Web)
