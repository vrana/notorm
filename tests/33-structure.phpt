--TEST--
Structure for non-conventional column
--FILE--
<?php
include_once dirname(__FILE__) . "/connect.inc.php";

class SoftwareConvention extends NotORM_Structure_Convention {
	function getReferencedTable($name, $table) {
		switch ($name) {
			case 'maintainer': return parent::getReferencedTable('author', $table);
		}
		return parent::getReferencedTable($name, $table);
	}
}

$convention = new NotORM($connection, new SoftwareConvention);
$maintainer = $convention->application[1]->maintainer;
echo $maintainer['name'] . "\n";
foreach ($maintainer->application()->via('maintainer_id') as $application) {
	echo "\t$application[title]\n";
}
?>
--EXPECTF--
Jakub Vrana
	Adminer
