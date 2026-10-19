<?php declare(strict_types=1); ?>
--TEST--
Calling __toString()
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ($software->application() as $application) {
	echo "$application\n";
}
?>
--EXPECTF--
31
32
33
34
