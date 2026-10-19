<?php declare(strict_types=1); ?>
--TEST--
Relations of an inserted row (bug #77)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application()->insert([
	"id" => 5,
	"author_id" => 12,
	"title" => "Texy",
	"web" => "",
	"slogan" => "The best humane Web text generator",
]);
$application->application_tag()->insert(["tag_id" => 21]);
$application->application_tag()->insert(["tag_id" => 22]);
echo $application->author["name"] . "\n";
echo count($application->application_tag()) . "\n";
foreach ($application->application_tag()->order("tag_id")->limit(1) as $application_tag) {
	echo $application_tag->tag["name"] . "\n";
}
$software->application_tag("application_id", 5)->delete();
$application->delete();
?>
--EXPECTF--
David Grudl
2
PHP
