<?php
namespace NotORM;

/** Loading and saving data, it's only cache so load() does not need to block until save()
*/
interface Cache {

	/** Load stored data
	* @param string $key
	* @return mixed null if not found
	*/
	function load($key);

	/** Save data
	* @param string $key
	* @param mixed $data
	* @return void
	*/
	function save($key, $data);

}
