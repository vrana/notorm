<?php
error_reporting(E_ALL | E_STRICT);
require __DIR__ . "/../vendor/autoload.php";

$driver = $argv[1] ?? "mysql"; // e.g. php tests/run-tests.php sqlite
$structure = null;
switch ($driver) {
	case "sqlite":
		$connection = new PDO("sqlite::memory:");
		$connection->exec("PRAGMA foreign_keys = ON"); // ON DELETE CASCADE in software.sql
		break;
	case "pgsql":
		$connection = new PDO("pgsql:host=localhost;dbname=software", "ODBC", "ODBC");
		$structure = new NotORM\Structure\Convention("id", "%s_id", "%s", "", false); // no table has a sequence
		break;
	default:
		$connection = new PDO("mysql:dbname=software", "ODBC", "ODBC");
}
if ($driver != "sqlite") { // SQLite is in memory
	$connection->exec("DROP TABLE IF EXISTS application_tag, application, tag, author");
}
foreach (preg_split('~;\r?\n~', file_get_contents(__DIR__ . "/software.sql")) as $query) {
	if (trim($query) != "" && ($driver == "mysql" || !preg_match('~^\s*/\*.*\*/\s*$~s', $query))) { // the MySQL specific comment is not a statement elsewhere
		$connection->exec($query);
	}
}
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
$software = new NotORM\NotORM($connection, $structure);
//~ $software->debug = true;
