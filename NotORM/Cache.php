<?php declare(strict_types=1);

namespace NotORM;

/** Loading and saving data, it's only cache so load() does not need to block until save() */
interface Cache {

	/** Version of the stored data, the keys start with it so that data stored by other versions are not used */
	const VERSION = 3;

	/** Load stored data
	* @return mixed null if not found
	*/
	function load(string $key): mixed;

	/** Save data */
	function save(string $key, mixed $data): void;

}
