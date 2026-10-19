<?php declare(strict_types=1); ?>
--TEST--
Quote floats and false
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo $software->application("id", 31.5) . "\n";
echo $software->application("id", false) . "\n";
echo count($software->application("id", 31.0)) . "\n";
?>
--EXPECTF--
SELECT * FROM application WHERE (id = 31.500000)
SELECT * FROM application WHERE (id = 0)
1
