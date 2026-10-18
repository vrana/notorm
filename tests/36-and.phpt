--TEST--
Calling and()
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ($software->application("author_id", 11)->and("maintainer_id", 11) as $application) {
	echo "$application[title]\n";
}
?>
--EXPECTF--
Adminer
