--TEST--
Using the same MultiResult several times (bug #12)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[1];
for ($i = 0; $i < 4; $i++) {
	echo count($application->application_tag()) . "\n";
}
?>
--EXPECTF--
2
2
2
2
