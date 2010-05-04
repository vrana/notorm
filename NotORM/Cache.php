<?php

/** Loading and saving data, it's only cache so load() does not need to block until save()
*/
interface NotORM_Cache {
	
	/** Load stored data
	* @param string
	* @return mixed
	*/
	function load($key);
	
	/** Save data
	* @param string
	* @param mixed
	* @return null
	*/
	function save($key, $data);
	
}

/** Cache using $_SESSION["NotORM"]
*/
class NotORM_Cache_Session implements NotORM_Cache {
	
	function load($key) {
		if (!isset($_SESSION["NotORM"]) || !array_key_exists($key, $_SESSION["NotORM"])) {
			return array();
		}
		return $_SESSION["NotORM"][$key];
	}
	
	function save($key, $data) {
		$_SESSION["NotORM"][$key] = $data;
	}
	
}

/** Cache using file
*/
class NotORM_Cache_File implements NotORM_Cache {
	private $filename, $data;
	
	function __construct($filename) {
		$this->filename = $filename;
		$this->data = unserialize(@file_get_contents($filename)); // @ - file can not exist
	}
	
	function __destruct() {
		// file_put_contents() is not atomic
		$fp = fopen($this->filename, "a");
		flock($fp, LOCK_EX);
		ftruncate($fp, 0);
		fwrite($fp, serialize($this->data));
		fclose($fp);
	}
	
	function load($key) {
		if (!$this->data || !array_key_exists($key, $this->data)) {
			return array();
		}
		return $this->data[$key];
	}
	
	function save($key, $data) {
		$this->data[$key] = $data;
	}
	
}

/** Cache storing data to the "notorm" table in database
*/
class NotORM_Cache_Database implements NotORM_Cache {
	private $connection;
	
	function __construct(PDO $connection) {
		$this->connection = $connection;
	}
	
	function load($key) {
		$result = $this->connection->prepare("SELECT data FROM notorm WHERE id = ?");
		$result->execute(array($key));
		$return = $result->fetchColumn();
		if (!$return) {
			return array();
		}
		return unserialize($return);
	}
	
	function save($key, $data) {
		// REPLACE is not supported by PostgreSQL and MS SQL
		$parameters = array(serialize($data), $key);
		$result = $this->connection->prepare("UPDATE notorm SET data = ? WHERE id = ?");
		$result->execute($parameters);
		if (!$result->rowCount()) {
			$result = $this->connection->prepare("INSERT INTO notorm (data, id) VALUES (?, ?)");
			try {
				@$result->execute($parameters); // @ - ignore duplicate key error
			} catch (PDOException $e) {
				if ($e->getCode() != "23000") { // "23000" - duplicate key
					throw $e;
				}
			}
		}
	}
	
}

// eAccelerator - user cache is obsoleted

/** Cache using "NotORM." prefix in Memcache
*/
class NotORM_Cache_Memcache implements NotORM_Cache {
	private $memcache;
	
	function __construct(Memcache $memcache) {
		$this->memcache = $memcache;
	}
	
	function load($key) {
		$return = $this->memcache->get("NotORM.$key");
		if ($return === false) {
			return array();
		}
		return $return;
	}
	
	function save($key, $data) {
		$this->memcache->set("NotORM.$key", $data);
	}
	
}

/** Cache using "NotORM." prefix in APC
*/
class NotORM_Cache_APC implements NotORM_Cache {
	
	function load($key) {
		$return = apc_fetch("NotORM.$key", $success);
		if (!$success) {
			return array();
		}
		return $return;
	}
	
	function save($key, $data) {
		apc_store("NotORM.$key", $data);
	}
	
}
