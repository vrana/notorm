--TEST--
through()
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->author() as $author) {
	foreach ($author->application()->through("maintainer_id") as $application) {
		echo "$author[name]: $application[title]\n";
	}
}
?>
--EXPECTF--
Jakub Vrana: Adminer
David Grudl: Nette
David Grudl: Dibi
