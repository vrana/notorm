--TEST--
Structure for non-conventional column
--FILE--
<?php
require_once __DIR__ . "/connect.inc.php";

class SoftwareConvention extends NotORM\Structure\Convention {
	function getReferencedTable($name, $table) {
		switch ($name) {
			case 'maintainer': return parent::getReferencedTable('author', $table);
		}
		return parent::getReferencedTable($name, $table);
	}
}

$convention = new NotORM\NotORM($connection, new SoftwareConvention);
$maintainer = $convention->application[1]->maintainer;
echo $maintainer['name'] . "\n";
foreach ($maintainer->application()->via('maintainer_id') as $application) {
	echo "\t$application[title]\n";
}
?>
--EXPECTF--
Jakub Vrana
	Adminer
