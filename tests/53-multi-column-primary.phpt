<?php declare(strict_types=1); ?>
--TEST--
Update and delete by a multi-column primary key (bug #78)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$discovery = new NotORM\NotORM($connection, new NotORM\Structure\Discovery($connection, foreign: "%s_id"));
$application_tag = $discovery->application_tag("application_id", 4)->order("tag_id")->fetch();
echo "$application_tag\n";
$application_tag["tag_id"] = 23;
echo $application_tag->update() . " updated\n";
echo "\n";

echo implode(", ", $discovery->application_tag("application_id", 4)->order("tag_id")->fetchPairs("tag_id", "tag_id")) . "\n";
$application_tag["tag_id"] = 21;
echo $application_tag->update() . " updated\n"; // by the new primary key
echo $application_tag->delete() . " deleted\n";
echo implode(", ", $discovery->application_tag("application_id", 4)->order("tag_id")->fetchPairs("tag_id", "tag_id")) . "\n";

$discovery->application_tag()->insert(["application_id" => 4, "tag_id" => 21]);
?>
--EXPECTF--
4,21
1 updated

22, 23
1 updated
1 deleted
22
