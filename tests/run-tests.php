<?php
$start = microtime(true);
foreach (glob("*.phpt") as $filename) {
	ob_start();
	include dirname(__FILE__) . "/$filename";
	if (!preg_match("~^--TEST--\n(.*)\n--FILE--\n(.*)\n--EXPECTF--\n(.*)~s", ob_get_clean(), $match)) {
		echo "wrong test in $filename\n";
	} elseif (rtrim($match[2]) != rtrim($match[3])) {
		echo "failed $filename ($match[1])\n";
	}
}
printf("%.3F s, %d KiB\n", microtime(true) - $start, memory_get_peak_usage() / 1024);
