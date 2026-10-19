<?php declare(strict_types=1); ?>
--TEST--
insertUpdate() with Literal parameters
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

foreach (["inserted", "updated"] as $slogan) {
	echo $software->application()->insertUpdate(["id" => 35], ["author_id" => 12, "title" => "Texy", "web" => "", "slogan" => new NotORM\Literal("?", $slogan)]) . "\n";
	echo $software->application[35]["slogan"] . "\n";
}
$software->application("id", 35)->delete();
?>
--EXPECTF--
1
inserted
2
updated
