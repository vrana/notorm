<?php
if (!isset($argv[1])) {
	// each database in a separate process because the tests declare classes and connect.inc.php is included once
	$failures = 0;
	foreach (array("mysql", "sqlite", "pgsql") as $driver) {
		$process = proc_open(array(PHP_BINARY, __FILE__, $driver), array(1 => array("pipe", "w")), $pipes);
		echo "$driver: " . stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		if (proc_close($process)) {
			$failures++;
		}
	}
	exit($failures ? 1 : 0);
}

$start = microtime(true);

$tests = glob(__DIR__ . "/*.phpt", GLOB_NOSORT);
natsort($tests);
$failures = 0;
foreach ($tests as $filename) {
	ob_start();
	require $filename;
	if (!preg_match("~^--TEST--\n(.*?)\n(?:--SKIPIF--\n(.*\n)?)?--FILE--\n(.*\n)?--EXPECTF--\n(.*)~s", str_replace("\r\n", "\n", ob_get_clean()), $match)) {
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
