--TEST--
Search and order items
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application("web LIKE ?", "http://%")->order("title")->limit(3) as $application) {
	echo "$application[title]\n";
}
?>
--EXPECTF--
Adminer
Dibi
JUSH
