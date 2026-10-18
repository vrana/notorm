<?php declare(strict_types=1);

error_reporting(E_ALL);
require __DIR__ . "/../vendor/autoload.php";

$driver = $argv[1] ?? "mysql"; // e.g. php tests/run-tests.php sqlite
if ($driver == "sqlite") {
	$connection = new PDO("sqlite::memory:");
	$connection->exec("PRAGMA foreign_keys = ON"); // ON DELETE CASCADE in software.sql
} else {
	$connection = new PDO("$driver:host=localhost;dbname=software", "ODBC", "ODBC");
	$connection->exec("DROP TABLE IF EXISTS application_tag, application, tag, author");
}
$structure = ($driver == "pgsql" ? new NotORM\Structure\Convention("id", "%s_id", "%s", "", false) : null); // no table has a sequence
foreach (preg_split('~;\r?\n~', file_get_contents(__DIR__ . "/software.sql")) as $query) {
	if (trim($query) != "" && ($driver == "mysql" || !preg_match('~^\s*/\*.*\*/\s*$~s', $query))) { // the MySQL specific comment is not a statement elsewhere
		$connection->exec($query);
	}
}
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
$software = new NotORM\NotORM($connection, $structure);
//~ $software->debug = true;
