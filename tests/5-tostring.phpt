--TEST--
Calling __toString()
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application() as $application) {
	echo "$application\n";
}
?>
--EXPECTF--
1
2
3
4
