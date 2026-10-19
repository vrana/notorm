<?php declare(strict_types=1); ?>
--TEST--
Insert from a query and from a Traversable
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

var_dump($software->tag()->insert($software->tag("id = ?", 21)->select("id + 10, name")));
var_dump($software->tag()->insert(new ArrayIterator(["id" => 32, "name" => "Go"])));
var_dump($software->application[2]->application_tag()->insertMulti([new ArrayIterator(["tag_id" => 31])]));
echo implode(", ", $software->tag()->order("id")->fetchPairs("id", "name")) . "\n";
echo implode(", ", $software->application_tag("application_id", 2)->order("tag_id")->fetchPairs("tag_id", "tag_id")) . "\n";
$software->application_tag("tag_id", 31)->delete();
$software->tag("id", [31, 32])->delete();
?>
--EXPECTF--
int(1)
int(1)
int(1)
PHP, MySQL, JavaScript, PHP, Go
23, 31
