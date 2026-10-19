--TEST--
Errors in queries
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
var_dump($software->tag("nonexistent", 1)->update(["name" => "Changed"]));
var_dump($software->tag("nonexistent", 1)->delete());
var_dump($software->tag()->max("nonexistent"));

$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
	$software->tag("nonexistent", 1)->fetch();
} catch (PDOException $exception) {
	echo "PDOException\n";
}
$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
?>
--EXPECTF--
bool(false)
bool(false)
NULL
PDOException
