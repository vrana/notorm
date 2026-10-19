<?php declare(strict_types=1); ?>
--TEST--
Multiple arguments
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[31];
$application_tags = $application->application_tag()
	->select("application_id", "tag_id")
	->order("application_id DESC", "tag_id DESC")
;
foreach ($application_tags as $application_tag) {
	echo "$application_tag[application_id] $application_tag[tag_id]\n";
}
?>
--EXPECTF--
31 22
31 21
