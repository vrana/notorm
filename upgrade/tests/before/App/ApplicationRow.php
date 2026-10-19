<?php
namespace App;

use NotORM_Row;
use NotORM_Result as Result;

/** Application with its tags
* @property-read Result $tags
*/
class ApplicationRow extends NotORM_Row {

	function offsetGet($key) {
		if ($key == "title") {
			return strtoupper(parent::offsetGet($key));
		}
		return parent::offsetGet($key);
	}

	function getId() {
		return $this->primary;
	}

	function update($data) {
		return parent::update($data);
	}

}
