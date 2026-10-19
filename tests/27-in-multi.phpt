<?php declare(strict_types=1); ?>
--TEST--
IN operator with MultiResult
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach ($software->author()->order("id") as $author) {
	foreach ($software->application_tag("application_id", $author->application())->order("application_id, tag_id") as $application_tag) {
		echo "$author: $application_tag[application_id]: $application_tag[tag_id]\n";
	}
}
?>
--EXPECTF--
11: 31: 21
11: 31: 22
11: 32: 23
12: 33: 21
12: 34: 21
12: 34: 22
