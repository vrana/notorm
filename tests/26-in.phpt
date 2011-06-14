--TEST--
IN operator
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

echo $software->application("maintainer_id", array())->count("*") . "\n";
echo $software->application("maintainer_id", array(11))->count("*") . "\n";
echo $software->application("NOT maintainer_id", array(11))->count("*") . "\n";
echo $software->application("NOT maintainer_id", array())->count("*") . "\n";
?>
--EXPECTF--
0
1
2
3
