--TEST--
Literal value with parameters
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->author()->select(new NotORM\Literal("0 + ? + ?", 1, 2))->fetch() as $val) { // 0 - PostgreSQL needs to know the type
	echo "$val\n";
}
?>
--EXPECTF--
3
