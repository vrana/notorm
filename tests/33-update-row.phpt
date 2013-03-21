--TEST--
Update db and also row
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$application = $software->application[1];
$data = array("title" => "Adminer2");
$application->update($data);

echo "$application[title]\n";

$data = array("title" => "Adminer");
$application->update($data);

echo "$application[title]\n";
?>
--EXPECTF--
Adminer2
Adminer
