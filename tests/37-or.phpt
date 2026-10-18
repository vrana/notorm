--TEST--
Calling or()
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ($software->application("author_id", 12)->or("maintainer_id", 11)->order("title") as $application) {
	echo "$application[title]\n";
}
?>
--EXPECTF--
Adminer
Dibi
Nette
