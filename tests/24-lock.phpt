<?php declare(strict_types=1); ?>
--TEST--
Select locking
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo $software->application()->lock() . "\n";
?>
--EXPECTF--
SELECT * FROM application FOR UPDATE
