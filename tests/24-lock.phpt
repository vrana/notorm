--TEST--
Select locking
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

echo $software->application()->lock() . "\n";
?>
--EXPECTF--
SELECT * FROM application FOR UPDATE
