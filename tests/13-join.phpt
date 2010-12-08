--TEST--
ORDER from other table
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application()->order("author.name, title") as $application) {
	echo $application->author["name"] . ": $application[title]\n";
}
?>
--EXPECTF--
David Grudl: Dibi
David Grudl: Nette
Jakub Vrana: Adminer
Jakub Vrana: JUSH
