--TEST--
ORDER from other table
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application()->order("author.name") as $application) {
	echo $application->author["name"] . ": $application[title]\n";
}
?>
--EXPECTF--
David Grudl: Nette
David Grudl: Dibi
Jakub Vrana: Adminer
Jakub Vrana: JUSH
