<?php declare(strict_types=1);

namespace NotORM;

/** Loading and saving data, it's only cache so load() does not need to block until save() */
interface Cache {

	/** Load stored data
	* @return mixed null if not found
	*/
	function load(string $key): mixed;

	/** Save data */
	function save(string $key, mixed $data): void;

}
