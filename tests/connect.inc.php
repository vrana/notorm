<?php declare(strict_types=1);

error_reporting(E_ALL);
require __DIR__ . "/../vendor/autoload.php";

$driver = $argv[1] ?? "mysql"; // e.g. php tests/run-tests.php sqlite
if ($driver == "sqlite") {
	$connection = new PDO("sqlite::memory:");
	$connection->exec("PRAGMA foreign_keys = ON"); // ON DELETE CASCADE in software.sql
} else {
	$dsn = match ($driver) {
		"sqlsrv" => "Server=localhost;Database=software", // not run by default
		"oci" => "dbname=//localhost/XEPDB1", // not run by default, the database is a schema
		"firebird" => "dbname=localhost:software", // not run by default, software is an alias in databases.conf
		default => "host=localhost;dbname=software",
	};
	$connection = new PDO("$driver:$dsn", "ODBC", "ODBC");
	if ($driver == "oci") {
		$connection->exec("ALTER SESSION SET CURRENT_SCHEMA = software");
	}
	if ($driver == "oci" || $driver == "firebird") {
		foreach (["application_tag", "application", "tag", "author"] as $table) {
			try {
				$connection->exec("DROP TABLE $table"); // IF EXISTS is not supported in Oracle 21 and Firebird 5
			} catch (PDOException $exception) {
			}
		}
	} else {
		$connection->exec("DROP TABLE IF EXISTS application_tag, application, tag, author");
	}
	if ($driver == "mysql") {
		$connection->exec("SET default_storage_engine = InnoDB"); // foreign keys
	}
}
$structure = ($driver == "pgsql" ? new NotORM\Structure\Convention(sequence: '') : null); // no table has a sequence
foreach (preg_split('~;\r?\n~', file_get_contents(__DIR__ . "/software.sql"), flags: PREG_SPLIT_NO_EMPTY) as $query) {
	$connection->exec($query);
}
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
$software = new NotORM\NotORM($connection, $structure);
//~ $software->debug = true;
