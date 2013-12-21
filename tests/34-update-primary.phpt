--TEST--
Update primary key of a row
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$application = $software->tag()->insert(array('id' => 24, 'name' => 'HTML'));
echo "$application[id]\n";
$application['id'] = 25;
echo "$application[id]\n";
echo $application->update() . "\n";
echo $application->delete() . "\n";
?>
--EXPECTF--
24
25
1
1
