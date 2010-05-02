--TEST--
Discovery test
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";
$discovery = new NotORM($connection, new NotORM_Structure_Discovery($connection));

foreach ($discovery->application() as $application) {
	echo "$application[title] (" . $application->author_id["name"] . ")\n";
	foreach ($application->application_tag() as $application_tag) {
		echo "\t" . $application_tag->tag_id["name"] . "\n";
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
