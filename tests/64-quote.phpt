--TEST--
Quote floats and false
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo $software->application("id", 1.5) . "\n";
echo $software->application("id", false) . "\n";
echo count($software->application("id", 1.0)) . "\n";
?>
--EXPECTF--
SELECT * FROM application WHERE (id = 1.500000)
SELECT * FROM application WHERE (id = 0)
1
