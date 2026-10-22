<?php declare(strict_types=1); ?>
--TEST--
Array offset is rejected
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$where = ["1) OR (1" => "1"]; // ?id[1) OR (1]=1

try {
	$software->application[$where];
} catch (InvalidArgumentException $exception) {
	echo $exception->getMessage() . "\n";
}

try {
	isset($software->application()->order("id")[$where]);
} catch (InvalidArgumentException $exception) {
	echo $exception->getMessage() . "\n";
}
?>
--EXPECTF--
Row offset must be an ID, use where()->fetch() for conditions.
Row offset must be an ID, use where()->fetch() for conditions.
