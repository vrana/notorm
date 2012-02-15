--TEST--
Array offset
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

$where = array(
	"author_id" => "11",
	"maintainer_id" => null,
);

echo $software->application[$where]["id"] . "\n";

$applications = $software->application()->order("id");
echo $applications[$where]["id"] . "\n";
?>
--EXPECTF--
2
2
