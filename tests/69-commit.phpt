<?php declare(strict_types=1); ?>
--TEST--
Commit a transaction
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$software->transaction = "BEGIN";
$software->tag()->insert(["id" => 24, "name" => "CSS"]);
$software->transaction = "COMMIT";
echo count($software->tag()) . " tags\n";
$software->tag("id", 24)->delete();
?>
--EXPECTF--
4 tags
