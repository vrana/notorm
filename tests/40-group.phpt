--TEST--
GROUP BY selects the grouped columns
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

echo $software->application()->group("author_id") . "\n";
echo $software->application()->group("author.name") . "\n";
echo $software->application()->select("author_id, COUNT(*) AS count")->group("author_id") . "\n";

foreach ($software->author()->order("id") as $author) {
	$applications = $author->application()->group("maintainer_id")->order("CASE WHEN maintainer_id IS NULL THEN 1 ELSE 0 END, maintainer_id"); // NULL is first in MySQL and last in PostgreSQL
	foreach ($applications as $application) {
		echo "$author[name]: " . ($application["maintainer_id"] ?? "NULL") . "\n";
	}
}
echo "$applications\n";
?>
--EXPECTF--
SELECT author_id FROM application GROUP BY author_id
SELECT author.name FROM application LEFT JOIN author ON application.author_id = author.id GROUP BY author.name
SELECT author_id, COUNT(*) AS count FROM application GROUP BY author_id
Jakub Vrana: 11
Jakub Vrana: NULL
David Grudl: 12
SELECT application.author_id, maintainer_id FROM application WHERE (application.author_id IN (11, 12)) GROUP BY application.author_id, maintainer_id ORDER BY application.author_id, CASE WHEN maintainer_id IS NULL THEN 1 ELSE 0 END, maintainer_id
