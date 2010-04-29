--TEST--
Subqueries
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$unknownBorn = $software->author("born", null); // authors with unknown date of born
foreach ($software->application("author_id", $unknownBorn) as $application) { // their applications
	echo "$application[title]\n";
}
?>
--EXPECTF--
Adminer
JUSH
Nette
Dibi
