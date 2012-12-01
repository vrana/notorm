<?php
/**
 * Based Zend_Cache_Memcached
 */
namespace NotORM\Cache;
use \Memcache as MCache;
class Memcache extends CacheAbstract implements CacheInterface
{          
    /**
     *
     * @var \Memcache
     */
    protected $memcache;    
      
    public function __construct($options = null) 
    {
        if (!extension_loaded('memcache')) {
           throw new \RuntimeException('The memcache extension must be loaded for using this backend !'); 
        }
        $this->memcache = new MCache;        
    }
    
    public function addServer(array $options)
    {           
        return call_user_func(array($this->memcache, 'addServer'), implode(',',$options));        
    }
        
    
    public function clear($key = null)
    {
        if (empty($key)) {
           return $this->memcache->flush();
        }
        return $this->memcache->delete($key, 0);
    }
    
    public function load($key)
    {
       $load = $this->memcache->get($key);
       if (is_array($load) && isset($load[0])) {
           return $this->getDataSerialize($load[0]);
       }
       return false;
    }
    
    public function save($key, $data, $flag = 0)
    {                       
        return $this->memcache->set($key, array($this->saveDataSerialize($data), time(), $this->lifetime), $flag, $this->lifetime);                
    }
    
    public function getMemcache()
    {
        return $this->memcache;
    }
    
   
}


