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
Adminer (Jakub Vrána)
	PHP
	MySQL
JUSH (Jakub Vrána)
	JavaScript
Nette (David Grudl)
	PHP
dibi (David Grudl)
	PHP
	MySQL
