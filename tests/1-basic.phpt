--TEST--
Basic operations
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application() as $application) {
	echo "$application[title] (" . $application->author["name"] . ")\n";
	foreach ($application->application_tag() as $application_tag) {
		echo "\t" . $application_tag->tag["name"] . "\n";
	}
}
?>
--EXPECTF--
Adminer (Jakub Vrana)
	PHP
	MySQL
JUSH (Jakub Vrana)
	JavaScript
Nette (David Grudl)
	PHP
Dibi (David Grudl)
	PHP
	MySQL
