<?php

namespace NotORM\Cache;
use \PDO;
class Database extends CacheAbstract implements CacheInterface
{
    /**
     *
     * @var \PDO
     */
    private $connection;
	
    public function __construct(PDO $connection) 
    {
        $this->setConnection($connection);
        $this->checkDatabase();
    }
    
    public function setConnection(PDO $connection)
    {
        $this->connection = $connection;
        return $this;
    }
    
    public function getConnection()
    {
        return $this->connection;
    }

    
    private function checkDatabase()
    {
        $row = $this->connection->query('SELECT data FROM notorm LIMIT 1')->fetch();
        if ($row === false) {
            throw new \RuntimeException('Not database notorm');
        }
    }
    
    public function load($key) 
    {
        $result = $this->connection->prepare("SELECT data FROM notorm WHERE id = ?");
        $result->execute(array($key));
        $data = $result->fetchColumn();
        if (!$data) {
                return null;
        }
        return $this->getDataSerialize($data);
    }
    
    public function save($key, $data) 
    {
        // REPLACE is not supported by PostgreSQL and MS SQL
		$parameters = array($this->saveDataSerialize($data), $key);
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
    
    public function clear($key = null) 
    {
        if (empty($key)) {
            return $this->clearAll();
        }
        $prepare = $this->connection->prepare('DELETE FROM notorm WHERE id ?');
        try {
         return $prepare->execute(array($key));
        } catch (\PDOException $e) {
            return $e->getMessage();
        }
    }
    
    public function clearAll()
    {
        $result = $this->connection
                ->query('SELECT id FROM notorm');
        if ($result->rowCount() > 0) {
            $rows = $result->fetchAll(PDO::FETCH_OBJ);
            foreach ($rows as $row) {
                $prepare = $this->connection->prepare('DELETE FROM notorm WHERE id ?');
                $prepare->execute(array($row->id));
            }
        }
        return true;
    }
}
