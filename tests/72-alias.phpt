--TEST--
Main table with a different name than the table (bug #83)
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

$structure = new class extends NotORM\Structure\Convention {
	function getReferencingTable(string $name, string $table): string {
		return ($name == "app" ? "application" : $name);
	}

	function getReferencedTable(string $name, string $table): string {
		return ($name == "maintainer" ? "author" : $name);
	}
};
$db = new NotORM\NotORM($connection, $structure);

$applications = $db->app("author.name", "Jakub Vrana")->order("app.id");
echo "$applications\n";
foreach ($applications as $application) {
	echo "$application[title]\n";
}
echo $db->app("author.name", "Jakub Vrana")->count("*") . "\n";
echo count($db->app("maintainer.name", "David Grudl")) . "\n"; // join with an alias
echo implode(", ", array_keys($db->app("application_tag:tag_id", 21)->order("app.id")->fetchPairs("app.id"))) . "\n";

foreach ($db->author()->order("id") as $author) {
	echo "$author[name]: " . implode(", ", $author->app()->order("title")->fetchPairs("id", "title")) . " (" . $author->app()->count("*") . ")\n";
}
?>
--EXPECTF--
SELECT app.* FROM application app LEFT JOIN author ON app.author_id = author.id WHERE (author.name = 'Jakub Vrana') ORDER BY app.id
Adminer
JUSH
2
2
1, 3, 4
Jakub Vrana: Adminer, JUSH (2)
David Grudl: Dibi, Nette (2)
