--TEST--
Admit
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

for ($i=0; $i < 2; $i++) {
	$application = $software->application()->admit(array("id" => 5), array("author_id" => 12, "title" => "Texy", "web" => "", "slogan" => "$i"));
	echo "{$application['id']},{$application['slogan']}\n";
}
$application = $software->application[5];
$application_tag = $application->application_tag()->admit(array("tag_id" => 21), array());
echo "{$application_tag['tag_id']},{$application_tag['application_id']}\n";
$software->application("id", 5)->delete();
?>
--EXPECTF--
5,0
5,1
21,5
