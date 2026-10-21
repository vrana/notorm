<?php declare(strict_types=1); ?>
--TEST--
Search and order items
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ($software->application("web LIKE ?", "https://%")->order("title")->limit(3) as $application) {
	echo "$application[title]\n";
}
?>
--EXPECTF--
Adminer
Dibi
JUSH
