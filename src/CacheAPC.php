<?php
namespace NotORM;


// eAccelerator - user cache is obsoleted


/** Cache using "NotORM." prefix in APC
*/
class CacheAPC implements CacheInterface {
	
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
