--TEST--
IN with NULL value
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ($software->application("maintainer_id", array(11, null))->order("id") as $application) {
	echo "$application[id]\n";
}
?>
--EXPECTF--
1
2
