<?php declare(strict_types=1);

// Prints the lines of NotORM/ which the tests didn't execute, used as auto_prepend_file, see README.md

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

register_shutdown_function(function (): void {
	$coverage = xdebug_get_code_coverage();
	$root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . "NotORM", FilesystemIterator::SKIP_DOTS));
	foreach ($files as $filename => $file) {
		$name = str_replace(DIRECTORY_SEPARATOR, "/", substr($filename, strlen($root)));
		if (!isset($coverage[$filename])) {
			echo "$name: not loaded\n";
			continue;
		}
		$lines = $coverage[$filename]; // 1 executed, -1 not executed, -2 dead code
		$executed = count(array_keys($lines, 1, true));
		$uncovered = array_keys($lines, -1, true);
		$ranges = [];
		foreach ($uncovered as $line) {
			$last = array_key_last($ranges);
			if ($last !== null && end($ranges[$last]) == $line - 1) {
				$ranges[$last][1] = $line;
			} else {
				$ranges[] = [$line];
			}
		}
		printf(
			"%s: %d%%%s\n",
			$name,
			100 * $executed / max(1, $executed + count($uncovered)),
			($ranges ? " (" . implode(", ", array_map(fn ($range) => implode("-", $range), $ranges)) . ")" : ""),
		);
	}
});
