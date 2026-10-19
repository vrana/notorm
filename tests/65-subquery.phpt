<?php declare(strict_types=1); ?>
--TEST--
Subquery with more columns and without rows
--SKIPIF--
<?php
require_once __DIR__ . "/connect.inc.php";

if ($driver == "sqlsrv") {
	echo "MS SQL doesn't support row values\n";
}
?>
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo count($software->application_tag("(application_id, tag_id)", $software->application_tag("tag_id", 21)->select("application_id, tag_id"))) . "\n";
echo count($software->application("id", $software->application_tag("tag_id", 99)->select("application_id"))) . "\n";
?>
--EXPECTF--
3
0
