<?php declare(strict_types=1);

// upgrades a copy of before/ and compares it with after/ and the printed messages with messages.txt

require __DIR__ . "/../upgrade.php";

$start = microtime(true);
$dir = str_replace("\\", "/", sys_get_temp_dir()) . "/notorm-upgrade-" . getmypid();

/** Get files in a directory
* @return list<string> paths relative to $dir
*/
$files = function (string $dir): array {
	$return = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $filename => $file) {
		$return[] = substr(str_replace("\\", "/", $filename), strlen($dir) + 1);
	}
	sort($return);
	return $return;
};

/** Run the upgrade
* @return string messages without $dir
*/
$run = fn (string $dir): string => implode("", array_map(fn (string $message): string => str_replace("$dir/", "", $message) . "\n", upgrade([$dir])));

foreach ($files(__DIR__ . "/before") as $filename) {
	if (!is_dir(dirname("$dir/$filename"))) {
		mkdir(dirname("$dir/$filename"), 0777, true);
	}
	copy(__DIR__ . "/before/$filename", "$dir/$filename");
}

$failures = [];
if (str_replace("\r\n", "\n", $run($dir)) !== str_replace("\r\n", "\n", file_get_contents(__DIR__ . "/messages.txt"))) {
	$failures[] = "messages.txt";
}
if ($files($dir) !== $files(__DIR__ . "/after")) {
	$failures[] = "the list of files";
}
foreach ($files(__DIR__ . "/after") as $filename) {
	if (!file_exists("$dir/$filename") || str_replace("\r\n", "\n", file_get_contents("$dir/$filename")) !== str_replace("\r\n", "\n", file_get_contents(__DIR__ . "/after/$filename"))) {
		$failures[] = "after/$filename";
	}
}
if (preg_match('~: (would be )?upgraded$~m', $run($dir))) {
	$failures[] = "the second run, it upgraded again";
}

if ($failures) {
	echo implode("", array_map(fn (string $failure): string => "failed $failure\n", $failures));
	echo "the upgraded files are in $dir\n";
} else {
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $filename => $file) {
		if ($file->isDir()) {
			rmdir($filename);
		} else {
			unlink($filename);
		}
	}
	rmdir($dir);
}

printf("%.3F s, %d KiB\n", microtime(true) - $start, memory_get_peak_usage() / 1024);
exit($failures ? 1 : 0);
