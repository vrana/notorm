<?php declare(strict_types=1); ?>
--TEST--
JSON serialization
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$applications = $software->application("id", [31, 32])->select("id, title")->order("id");
echo json_encode($applications, JSON_NUMERIC_CHECK) . "\n"; // JSON_NUMERIC_CHECK - some drivers return numbers as strings
$software->jsonAsArray = true;
echo json_encode($applications, JSON_NUMERIC_CHECK) . "\n";
$software->jsonAsArray = false;
echo json_encode($software->application("id", 31)->select("id, title")->fetch(), JSON_NUMERIC_CHECK) . "\n";
?>
--EXPECTF--
{"31":{"id":31,"title":"Adminer"},"32":{"id":32,"title":"JUSH"}}
[{"id":31,"title":"Adminer"},{"id":32,"title":"JUSH"}]
{"id":31,"title":"Adminer"}
