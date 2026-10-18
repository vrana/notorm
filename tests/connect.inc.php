<?php
error_reporting(E_ALL | E_STRICT);
require dirname(__FILE__) . "/../vendor/autoload.php";

switch ($argv[1] ?? "mysql") { // e.g. php tests/run-tests.php sqlite
	case "sqlite":
		$connection = new PDO("sqlite::memory:");
		$connection->exec("PRAGMA foreign_keys = ON"); // ON DELETE CASCADE in software.sql
		foreach (preg_split('~;\r?\n~', file_get_contents(dirname(__FILE__) . "/software.sql")) as $query) {
			$connection->exec($query);
		}
		break;
	default:
		$connection = new PDO("mysql:dbname=software", "ODBC", "ODBC");
}
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
$software = new NotORM\NotORM($connection);
//~ $software->debug = true;
