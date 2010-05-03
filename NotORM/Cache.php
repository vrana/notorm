<?php

/** Loading and saving data
*/
interface NotORM_Cache {
	
	/** Load stored data
	* @param string
	* @return array|null
	*/
	function load($key);
	
	/** Save data
	* @param string
	* @param array|null
	* @return null
	*/
	function save($key, $data);
	
}

/** Cache using $_SESSION["NotORM"]
*/
class NotORM_Cache_Session implements NotORM_Cache {
	
	function load($key) {
		if (!isset($_SESSION["NotORM"][$key])) {
			return array();
		}
		return $_SESSION["NotORM"][$key];
	}
	
	function save($key, $data) {
		$_SESSION["NotORM"][$key] = $data;
	}
	
	function delete() {
		unset($_SESSION["NotORM"]);
	}
	
}

//! File, Memcache
