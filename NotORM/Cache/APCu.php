<?php
namespace NotORM\Cache;

/** Cache using "NotORM." prefix in APCu
*/
class APCu implements \NotORM\Cache {

	function load($key) {
		$return = apcu_fetch("NotORM.$key", $success);
		if (!$success) {
			return null;
		}
		return $return;
	}

	function save($key, $data) {
		apcu_store("NotORM.$key", $data);
	}

}
