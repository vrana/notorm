--TEST--
Literal value with parameters
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->author()->select(new NotORM_Literal("? + ?", 1, 2))->fetch() as $val) {
	echo "$val\n";
}
?>
--EXPECTF--
3
