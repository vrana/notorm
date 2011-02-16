--TEST--
INSERT or UPDATE
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

for ($i=0; $i < 2; $i++) {
	echo $software->application()->insert_update(array("id" => 5), array("author_id" => 12, "title" => "Texy", "web" => "", "slogan" => "$i")) . "\n";
}
$software->application("id", 5)->delete();
?>
--EXPECTF--
1
2
