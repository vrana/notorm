<?php
namespace NotORM\Tests;

/**
 * Class SoftwareConvention
 * For testStructure()
 * @package NotORM
 */
class SoftwareConvention extends \NotORM\StructureConvention {
	public function getReferencedTable($name, $table) {
		switch ($name) {
			case 'maintainer': return parent::getReferencedTable('author', $table);
		}
		return parent::getReferencedTable($name, $table);
	}
}