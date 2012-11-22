<?php

namespace NotORM\Cache;

use \InvalidArgumentException as InvalidArgument;

abstract class CacheAbstract 
{
    protected $lifetime = 3600;
    protected $automaticSerialize = false;

    /**
     * 
     * @param integer $lifetime
     * @return \NotORM\Cache\CacheAbstract
     * @throws \InvalidArgumentException
     */
    public function setLifetime($lifetime = 3600)
    {
        if (!is_int($lifetime)) {
            throw new InvalidArgument('This lifetime only integer');
        }
        
        if ($lifetime < 0) {
            throw new InvalidArgument('This lifetime don\'t negative');
        }
        
        $this->lifetime = $lifetime;
        return $this;
    }
    
    public function getLifeTime()
    {
        return $this->lifetime;
    }
    
    /**
     * 
     * @param boolean $isSerialize
     * @return \NotORM\Cache\CacheAbstract
     * @throws \InvalidArgumentException
     */
    public function setAutomaticSerialize($isSerialize = false)
    {
        if (!is_bool($isSerialize)) {
            throw new InvalidArgument("This only boolean");
        }
        $this->automaticSerialize = $isSerialize;
        return $this;
    }
    
    protected function saveDataSerialize($data)
    {
        if ($this->automaticSerialize) {
            return serialize($data);
        }
        return $data;
    }
    
    protected function getDataSerialize($data)
    {
        if ($this->automaticSerialize) {
            return unserialize($data);
        }
        return $data;
    }
        
}


