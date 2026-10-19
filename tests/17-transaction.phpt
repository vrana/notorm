<?php declare(strict_types=1); ?>
--TEST--
Transactions
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$software->transaction = "BEGIN";
$software->tag()->insert(["id" => 99, "name" => "Test"]);
echo $software->tag[99] . "\n";
$software->transaction = "ROLLBACK";
echo $software->tag[99] . "\n";
?>
--EXPECTF--
99

