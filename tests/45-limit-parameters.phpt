<?php declare(strict_types=1); ?>
--TEST--
Parameters in MultiResult with limit (bug #23)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ($software->application()->order("id") as $application) {
	foreach ($application->application_tag()->order(new NotORM\Literal("CASE WHEN tag_id = ? THEN 0 ELSE 1 END", 22))->limit(1) as $application_tag) {
		echo "$application[id]: $application_tag[tag_id]\n";
	}
}
?>
--EXPECTF--
31: 22
32: 23
33: 21
34: 22
