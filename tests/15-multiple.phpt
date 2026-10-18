--TEST--
Multiple arguments
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[1];
foreach ($application->application_tag()
	->select("application_id", "tag_id")
	->order("application_id DESC", "tag_id DESC")
as $application_tag) {
	echo "$application_tag[application_id] $application_tag[tag_id]\n";
}
?>
--EXPECTF--
1 22
1 21
