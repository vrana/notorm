--TEST--
Lock
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

echo $software->application() . "\n";
echo $software->application()->lock() . "\n";
echo $software->application()->lock(false) . "\n";
?>
--EXPECTF--
SELECT * FROM application
SELECT * FROM application FOR UPDATE
SELECT * FROM application LOCK IN SHARE MODE
