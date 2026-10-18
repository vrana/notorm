--TEST--
DateTime processing
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$date = new DateTime("2011-08-30");

$software->application()->insert(array(
	"id" => 5,
	"author_id" => 11,
	"title" => $date,
	"slogan" => new NotORM\Literal("?", $date),
));

$application = $software->application()->where("title = ?", $date)->fetch();
echo "$application[slogan]\n";
$application->delete();
?>
--EXPECTF--
2011-08-30 00:00:00
