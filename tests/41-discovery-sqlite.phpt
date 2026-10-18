--TEST--
Discovery in SQLite
--SKIPIF--
<?php
echo (extension_loaded("pdo_sqlite") ? "" : "pdo_sqlite is not available.\n");
?>
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$sqlite = new PDO("sqlite::memory:");
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
foreach (explode(";\n", str_replace("\r\n", "\n", file_get_contents(dirname(__FILE__) . "/software.sql"))) as $query) {
	if (trim($query) != "") {
		$sqlite->exec($query);
	}
}
$discovery = new NotORM\NotORM($sqlite, new NotORM\Structure\Discovery($sqlite));

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
