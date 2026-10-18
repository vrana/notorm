--TEST--
ORDER from other table
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

foreach ($software->application()->order("author.name, title") as $application) {
	echo $application->author["name"] . ": $application[title]\n";
}
echo "\n";

// SELECT application_tag.* with GROUP BY tag_id requires sql_mode without ONLY_FULL_GROUP_BY (default since MySQL 5.7.5)
foreach ($software->application_tag("application.author.name", "Jakub Vrana")->group("application_tag.tag_id") as $application_tag) {
	echo $application_tag->tag["name"] . "\n";
}
?>
--EXPECTF--
David Grudl: Dibi
David Grudl: Nette
Jakub Vrana: Adminer
Jakub Vrana: JUSH

PHP
MySQL
JavaScript
