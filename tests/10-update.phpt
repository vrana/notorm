--TEST--
Insert, update, delete
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$id = 5; // auto_increment is disabled in demo
$software->application(array(
	"id" => $id,
	"author_id" => $software->author[12],
	"title" => "Texy",
	"web" => "",
	"slogan" => "The best humane Web text generator",
));
echo $software->application[$id]["title"] . "\n";

echo $software->application("id", $id)->update(array(
	"web" => "http://texy.info/",
)) . " row updated.\n";
echo $software->application[$id]["web"] . "\n";

echo $software->application("id", $id)->delete() . " row deleted.\n";
echo count($software->application("id", $id)) . " rows found.\n";
?>
--EXPECTF--
Texy
1 row updated.
http://texy.info/
1 row deleted.
0 rows found.
