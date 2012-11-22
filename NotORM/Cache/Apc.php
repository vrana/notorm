<?php

namespace NotORM\Cache;

class Apc extends CacheAbstract implements CacheInterface
{
    public function load($key) 
    {
        $data = apc_fetch("NotORM.$key", $success);
        if (!$success) {
            return null;
        }
        return $this->getDataSerialize($data);
    }
    
    public function save($key, $data) 
    {
        return apc_store("NotORM.$key", $this->saveDataSerialize($data));
    }
    
    public function clear($key = null)
    {
        if (is_null($key)) {
            return apc_clear_cache();
        }
        return apc_delete($key);
    }
}


