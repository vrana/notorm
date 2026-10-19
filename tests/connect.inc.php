<?php declare(strict_types=1);

error_reporting(E_ALL);
require __DIR__ . "/../vendor/autoload.php";

$driver = $argv[1] ?? "mysql"; // e.g. php tests/run-tests.php sqlite
$dsn = match ($driver) {
	"firebird" => "dbname=localhost:software", // not run by default, software is an alias in databases.conf
	"oci" => "dbname=//localhost/XEPDB1", // not run by default, the database is a schema
	"sqlite" => ":memory:",
	"sqlsrv" => "Server=localhost;Database=software", // not run by default
	default => "host=localhost;dbname=software",
};
$connection = new PDO("$driver:$dsn", "ODBC", "ODBC");
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$connection->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

$tables = ["application_tag", "application", "tag", "author", "notorm", "setting"];
if ($driver == "sqlite") {
	$connection->exec("PRAGMA foreign_keys = ON"); // ON DELETE CASCADE in software.sql
} elseif ($driver == "oci" || $driver == "firebird") {
	if ($driver == "oci") {
		$connection->exec("ALTER SESSION SET CURRENT_SCHEMA = software");
	}
	foreach ($tables as $table) {
		@$connection->exec("DROP TABLE $table"); // @ - the table may not exist, IF EXISTS is not supported in Oracle 21 and Firebird 5
	}
} else {
	$connection->exec("DROP TABLE IF EXISTS " . implode(", ", $tables));
	if ($driver == "mysql") {
		$connection->exec("SET default_storage_engine = InnoDB"); // foreign keys
	}
}

foreach (preg_split('~;\r?\n~', file_get_contents(__DIR__ . "/software.sql"), flags: PREG_SPLIT_NO_EMPTY) as $query) {
	$connection->exec($query);
}

$structure = ($driver == "pgsql" ? new NotORM\Structure\Convention(sequence: '') : null); // no table has a sequence
$software = new NotORM\NotORM($connection, $structure);
//~ $software->debug = true;
