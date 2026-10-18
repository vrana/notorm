<?php
namespace NotORM\Cache;

/** Cache using "NotORM." prefix in APC
*/
class APC implements \NotORM\Cache {
	
	function load($key) {
		$return = apc_fetch("NotORM.$key", $success);
		if (!$success) {
			return null;
		}
		return $return;
	}
	
	function save($key, $data) {
		apc_store("NotORM.$key", $data);
	}
	
}
