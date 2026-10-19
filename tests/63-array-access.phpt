--TEST--
Check and remove columns of rows and rows of results
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$application = $software->application[2];
var_dump(isset($application->author), isset($application->maintainer));
var_dump(isset($application["title"]), isset($application["nonexistent"]));
var_dump(@$application["nonexistent"]); // @ - undefined column
echo count($application) . " columns\n";
unset($application["web"]);
unset($application->author);
echo implode(", ", array_keys(iterator_to_array($application))) . "\n";

$tags = $software->tag()->order("id");
unset($tags[22]);
$tags[24] = $software->tag[21];
echo implode(", ", array_keys(iterator_to_array($tags))) . "\n";
?>
--EXPECTF--
bool(true)
bool(false)
bool(true)
bool(false)
NULL
6 columns
id, maintainer_id, title, slogan
21, 23, 24
