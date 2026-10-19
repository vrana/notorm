<?php declare(strict_types=1); ?>
--TEST--
fetch() from more instances of the same MultiResult (bug #59)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[1];
$tags1 = $application->application_tag()->order("tag_id");
$tags2 = $application->application_tag()->order("tag_id");
echo $tags1->fetch("tag_id") . "\n";
echo $tags2->fetch("tag_id") . "\n";
echo $tags1->fetch("tag_id") . "\n";
echo $tags2->fetch("tag_id") . "\n";
var_dump($tags1->fetch());
?>
--EXPECTF--
21
21
22
22
NULL
