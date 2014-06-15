--TEST--
via()
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->author() as $author) {
	$applications = $author->application()->via("maintainer_id");
	foreach ($applications as $application) {
		echo "$author[name]: $application[title]\n";
	}
}
echo "$applications\n";
?>
--EXPECTF--
Jakub Vrana: Adminer
David Grudl: Nette
David Grudl: Dibi
SELECT * FROM application WHERE (application.maintainer_id IN (11, 12))
