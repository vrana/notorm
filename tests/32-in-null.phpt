--TEST--
IN with NULL value
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application("maintainer_id", array(11, null)) as $application) {
	echo "$application[id]\n";
}
?>
--EXPECTF--
1
2
