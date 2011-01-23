--TEST--
Transactions
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$software->transaction = "BEGIN";
$software->tag()->insert(array("id" => 99, "name" => "Test"));
echo $software->tag[99] . "\n";
$software->transaction = "ROLLBACK";
echo $software->tag[99] . "\n";
?>
--EXPECTF--
99

