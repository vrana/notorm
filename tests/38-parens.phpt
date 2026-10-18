--TEST--
Calling or()
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$applications = $software->application()
	->where("(author_id", 11)->and("maintainer_id", 11)->where(")")
	->or("(author_id", 12)->and("maintainer_id", 12)->where(")")
;

foreach ($applications->order("title") as $application) {
	echo "$application[title]\n";
}
?>
--EXPECTF--
Adminer
Dibi
Nette
