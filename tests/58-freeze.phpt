--TEST--
Freeze
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$software->freeze = true;
var_dump($software->tag()->insert(["id" => 24, "name" => "CSS"]));
var_dump($software->tag()->insertMulti([["id" => 24, "name" => "CSS"], ["id" => 25, "name" => "HTML"]]));
var_dump($software->tag("id", 21)->update(["name" => "Changed"]));
var_dump($software->tag()->insertUpdate(["id" => 21], ["name" => "Changed"]));
var_dump($software->tag("id", 21)->delete());
$software->freeze = false;
echo implode(", ", $software->tag()->order("id")->fetchPairs("id", "name")) . "\n";
?>
--EXPECTF--
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
PHP, MySQL, JavaScript
