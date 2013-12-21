--TEST--
Using the same MultiResult several times
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

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
