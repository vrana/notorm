--TEST--
Debug and debugTimer
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$software->debug = function (string $query, array $parameters): void {
	echo "$query -- " . implode(", ", $parameters) . "\n";
};
$software->author("name = ?", "Jakub Vrana")->fetch();

$software->debug = fn () => false; // skip the query
echo count($software->author()) . "\n";

$log = tempnam(sys_get_temp_dir(), "notorm");
ini_set("error_log", $log);
$software->debug = true;
$software->author("name = ?", "Jakub Vrana")->fetch();
ini_restore("error_log");
echo preg_replace('~^\[[^]]*] .*[/\\\\]~', '', trim(file_get_contents($log))) . "\n"; // remove the date and the directory
unlink($log);

$queries = 0;
$software->debug = false;
$software->debugTimer = function () use (&$queries): void {
	$queries++;
};
foreach ($software->application() as $application) {
	$application->author["name"];
}
$software->debugTimer = null;
echo "$queries queries\n";
?>
--EXPECTF--
SELECT * FROM author WHERE (name = ?) -- Jakub Vrana
0
55-debug.phpt:18:SELECT * FROM author WHERE (name = ?); -- 'Jakub Vrana'
2 queries
