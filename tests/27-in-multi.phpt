--TEST--
IN operator with MultiResult
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->author()->order("id") as $author) {
	foreach ($software->application_tag("application_id", $author->application())->order("application_id, tag_id") as $application_tag) {
		echo "$author: $application_tag[application_id]: $application_tag[tag_id]\n";
	}
}
?>
--EXPECTF--
11: 1: 21
11: 1: 22
11: 2: 23
12: 3: 21
12: 4: 21
12: 4: 22
