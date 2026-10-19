<?php declare(strict_types=1); ?>
--TEST--
Update row through property
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[31];
$application->author = $software->author[12];
echo $application->update() . "\n";
$application->update(["author_id" => 11]);
?>
--EXPECTF--
1
