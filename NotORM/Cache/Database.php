<?php declare(strict_types=1);

namespace NotORM\Cache;

/** Cache storing data to the "notorm" table in database
* CREATE TABLE notorm (id varchar(255) NOT NULL, data text NOT NULL, PRIMARY KEY (id))
* Oracle doesn't support text, use varchar2(4000) there
*/
class Database implements \NotORM\Cache {
	function __construct(
		private \PDO $connection,
	) {
	}

	function load(string $key): mixed {
		$result = $this->connection->prepare("SELECT data FROM notorm WHERE id = ?");
		$result->execute([$key]);
		$return = $result->fetchColumn();
		if (!$return) {
			return null;
		}
		return unserialize($return);
	}

	function save(string $key, mixed $data): void {
		// REPLACE is not supported by PostgreSQL and MS SQL
		$parameters = [serialize($data), $key];
		$result = $this->connection->prepare("UPDATE notorm SET data = ? WHERE id = ?");
		$result->execute($parameters);
		if (!$result->rowCount()) {
			$result = $this->connection->prepare("INSERT INTO notorm (data, id) VALUES (?, ?)");
			try {
				@$result->execute($parameters); // @ - ignore duplicate key error
			} catch (\PDOException $e) {
				if ($e->getCode() != "23000") { // "23000" - duplicate key
					throw $e;
				}
			}
		}
	}

}
