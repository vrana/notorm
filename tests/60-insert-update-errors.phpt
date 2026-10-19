--TEST--
insertUpdate() without data to update and with an error
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo $software->application_tag()->insertUpdate(["application_id" => 1, "tag_id" => 21], []) . "\n"; // duplicate key with nothing to update

$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
var_dump($software->tag()->insertUpdate(["id" => 24], ["nonexistent" => 1]));

$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
	$software->tag()->insertUpdate(["id" => 24], ["nonexistent" => 1]);
} catch (PDOException $exception) {
	echo "PDOException\n";
}

$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
set_error_handler(function (): bool {
	echo "warning\n";
	return true;
});
var_dump($software->tag()->insertUpdate(["id" => 24], ["nonexistent" => 1]));
restore_error_handler();
echo count($software->tag()) . " tags\n";
?>
--EXPECTF--
0
bool(false)
PDOException
warning
bool(false)
3 tags
