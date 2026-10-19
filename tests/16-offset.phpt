<?php declare(strict_types=1); ?>
--TEST--
Limit and offset
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[31];
foreach ($application->application_tag()->order("tag_id")->limit(1, 1) as $application_tag) {
	echo $application_tag->tag["name"] . "\n";
}
echo "\n";

foreach ($software->application() as $application) {
	foreach ($application->application_tag()->order("tag_id")->limit(1, 1) as $application_tag) {
		echo $application_tag->tag["name"] . "\n";
	}
}
?>
--EXPECTF--
MySQL

MySQL
MySQL
