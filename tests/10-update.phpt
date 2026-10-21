<?php declare(strict_types=1); ?>
--TEST--
Insert, update, delete
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$id = 35; // auto_increment is disabled in demo
$application = $software->application()->insert([
	"id" => $id,
	"author_id" => $software->author[12],
	"title" => new NotORM\Literal("'Texy'"),
	"web" => "",
	"slogan" => "The best humane Web text generator",
]);
$application_tag = $application->application_tag()->insert(["tag_id" => 21]);

// retrieve the really stored value
$application = $software->application[$id];
echo $application["title"] . "\n";

$application["web"] = "https://texy.info/";
echo $application->update() . " row updated.\n";
echo $software->application[$id]["web"] . "\n";

$software->application_tag("application_id", 35)->delete(); // foreign keys may be disabled
echo $application->delete() . " row deleted.\n";
echo count($software->application("id", $id)) . " rows found.\n";
?>
--EXPECTF--
Texy
1 row updated.
https://texy.info/
1 row deleted.
0 rows found.
