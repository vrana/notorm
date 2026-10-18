--TEST--
IN operator
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo $software->application("maintainer_id", [])->count("*") . "\n";
echo $software->application("maintainer_id", [11])->count("*") . "\n";
echo $software->application("NOT maintainer_id", [11])->count("*") . "\n";
echo $software->application("NOT maintainer_id", [])->count("*") . "\n";
?>
--EXPECTF--
0
1
2
3
