--TEST--
UPDATE and DELETE with ORDER BY and LIMIT (bug #136)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$software->tag()->insertMulti([
	["id" => 24, "name" => "CSS"],
	["id" => 25, "name" => "HTML"],
	["id" => 26, "name" => "SQL"],
]);
echo $software->tag("id > ?", 23)->order("id")->update(["name" => "New"]) . " rows updated.\n";
echo $software->tag("id > ?", 23)->order("id DESC")->limit(1)->update(["name" => "Last"]) . " row updated.\n";
echo implode(", ", $software->tag("id > ?", 23)->order("id")->fetchPairs("id", "name")) . "\n";
echo $software->tag("id > ?", 23)->order("id")->limit(1)->delete() . " row deleted.\n";
echo implode(", ", $software->tag("id > ?", 23)->order("id")->fetchPairs("id", "name")) . "\n";
echo $software->tag("id > ?", 23)->order("id")->delete() . " rows deleted.\n";
?>
--EXPECTF--
3 rows updated.
1 row updated.
New, New, Last
1 row deleted.
New, Last
2 rows deleted.
