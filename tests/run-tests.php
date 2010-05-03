<?php
$start = microtime(true);
foreach (glob(dirname(__FILE__) . "/*.phpt") as $filename) {
	ob_start();
	include $filename;
	if (!preg_match("~^--TEST--\n(.*)\n--FILE--\n(.*\n)?--EXPECTF--\n(.*)~s", ob_get_clean(), $match)) {
		echo "wrong test in $filename\n";
	} elseif ($match[2] !== $match[3]) {
		echo "failed $filename ($match[1])\n";
	}
}
printf("%.3F s, %d KiB\n", microtime(true) - $start, memory_get_peak_usage() / 1024);
