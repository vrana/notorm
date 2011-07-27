--TEST--
Update row through property
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$application = $software->application[1];
$application->author = $software->author[12];
echo $application->update() . "\n";
$application->update(array("author_id" => 11));
?>
--EXPECTF--
1
