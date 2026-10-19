<?php declare(strict_types=1); ?>
--TEST--
Discovery test
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";
$discovery = new NotORM\NotORM($connection, new NotORM\Structure\Discovery($connection));

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
