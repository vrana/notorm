<?php
namespace App;

use NotORM\Result;

/** Application with its tags
* @property-read Result $tags
*/
class ApplicationRow extends \NotORM\Row {

	function offsetGet($key): mixed {
		if ($key == "title") {
			return strtoupper(parent::offsetGet($key));
		}
		return parent::offsetGet($key);
	}

	function getId() {
		return $this->id;
	}

	function update($data): int|false {
		return parent::update($data);
	}

}
