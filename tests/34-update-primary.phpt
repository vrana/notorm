<?php declare(strict_types=1); ?>
--TEST--
Update primary key of a row
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->tag()->insert(['id' => 24, 'name' => 'HTML']);
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
