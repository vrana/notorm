<?php declare(strict_types=1); ?>
--TEST--
Binary data (bug #93)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$logo = function (NotORM\Row $application): ?string {
	$logo = $application["logo"];
	return (is_resource($logo) ? stream_get_contents($logo) : $logo); // PostgreSQL and Oracle return a stream
};

foreach ([gzencode("NotORM"), "\0NotORM"] as $data) { // not UTF-8, UTF-8 with a NUL byte
	$software->application("id", 31)->update(["logo" => $data]);
	var_dump($logo($software->application[31]) === $data);
}
$software->application("id", 31)->update(["logo" => null]);
?>
--EXPECTF--
bool(true)
bool(true)
