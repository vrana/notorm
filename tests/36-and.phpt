--TEST--
Calling and()
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application("author_id", 11)->and("maintainer_id", 11) as $application) {
	echo "$application[title]\n";
}
?>
--EXPECTF--
Adminer
