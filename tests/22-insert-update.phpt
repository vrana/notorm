<?php declare(strict_types=1); ?>
--TEST--
INSERT or UPDATE
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

for ($i=0; $i < 2; $i++) {
	echo $software->application()->insertUpdate(["id" => 35], ["author_id" => 12, "title" => "Texy", "web" => "", "slogan" => "$i"]) . "\n";
}
$application = $software->application[35];
echo $application->application_tag()->insertUpdate(["tag_id" => 21], []) . "\n";
$software->application("id", 35)->delete();
?>
--EXPECTF--
1
2
1
