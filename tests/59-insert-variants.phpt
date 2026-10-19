<?php declare(strict_types=1); ?>
--TEST--
Insert from a query and from a Traversable
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

var_dump($software->tag()->insert($software->tag("id = ?", 21)->select("id + 3, name")));
var_dump($software->tag()->insert(new ArrayIterator(["id" => 25, "name" => "Go"])));
var_dump($software->application[32]->application_tag()->insertMulti([new ArrayIterator(["tag_id" => 24])]));
echo implode(", ", $software->tag()->order("id")->fetchPairs("id", "name")) . "\n";
echo implode(", ", $software->application_tag("application_id", 32)->order("tag_id")->fetchPairs("tag_id", "tag_id")) . "\n";
$software->application_tag("tag_id", 24)->delete();
$software->tag("id", [24, 25])->delete();
?>
--EXPECTF--
int(1)
int(1)
int(1)
PHP, MySQL, JavaScript, PHP, Go
23, 24
