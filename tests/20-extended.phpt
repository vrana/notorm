<?php declare(strict_types=1); ?>
--TEST--
Extended insert
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[33];
$application->application_tag()->insert(["tag_id" => 22], ["tag_id" => 23]);
foreach ($application->application_tag()->order("tag_id DESC") as $application_tag) {
	echo "$application_tag[application_id] $application_tag[tag_id]\n";
}
$application->application_tag("tag_id", [22, 23])->delete();
?>
--EXPECTF--
33 23
33 22
33 21
