<?php declare(strict_types=1);

if (!isset($argv[1])) {
	// each database in a separate process because the tests declare classes and connect.inc.php is included once
	$failures = 0;
	foreach (["mysql", "sqlite", "pgsql"] as $driver) {
		$process = proc_open([PHP_BINARY, __FILE__, $driver], [1 => ["pipe", "w"]], $pipes);
		echo "$driver: " . stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		if (proc_close($process)) {
			$failures++;
		}
	}
	exit($failures ? 1 : 0);
}

require_once __DIR__ . "/connect.inc.php"; // the tests share its variables, their require_once doesn't include it again

// each test in its own scope so that it can't overwrite the variables of this file or of the other tests
$run = function (string $filename): string {
	global $connection, $software, $driver; // used by the tests
	ob_start();
	require $filename;
	return ob_get_clean();
};

$start = microtime(true);

$tests = glob(__DIR__ . "/*.phpt", GLOB_NOSORT);
natsort($tests);
$failures = 0;
foreach ($tests as $filename) {
	if (!preg_match("~^--TEST--\n(.*?)\n(?:--SKIPIF--\n(.*\n)?)?--FILE--\n(.*\n)?--EXPECTF--\n(.*)~s", str_replace("\r\n", "\n", $run($filename)), $match)) {
		echo "wrong test in $filename\n";
		$failures++;
	} elseif ($match[2]) {
		echo "skipped $filename ($match[1]): $match[2]";
	} elseif ($match[3] !== $match[4]) {
		echo "failed $filename ($match[1])\n";
		$failures++;
	}
}

printf("%.3F s, %d KiB\n", microtime(true) - $start, memory_get_peak_usage() / 1024);
exit($failures ? 1 : 0);
