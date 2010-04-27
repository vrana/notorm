--TEST--
Search and order items
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application("web != ?", "")->order("title")->limit(10) as $application) {
	echo "$application[title]\n";
}
?>
--EXPECTF--
Adminer
dibi
JUSH
Nette
