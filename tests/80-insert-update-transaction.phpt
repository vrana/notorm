<?php declare(strict_types=1); ?>
--TEST--
insertUpdate() in a transaction
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$software->transaction = "BEGIN";
echo $software->tag()->insertUpdate(["id" => 24], ["name" => "CSS"]) . "\n";
echo $software->tag()->insertUpdate(["id" => 24], ["name" => "HTML"]) . "\n";
echo $software->application_tag()->insertUpdate(["application_id" => 31, "tag_id" => 21], []) . "\n"; // duplicate key with nothing to update
$software->transaction = "COMMIT";
echo $software->tag[24]["name"] . "\n";
$software->tag("id", 24)->delete();
?>
--EXPECTF--
1
2
0
HTML
