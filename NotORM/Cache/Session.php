<?php

namespace NotORM\Cache;

class Session extends CacheAbstract implements CacheInterface
{    
    private $sessionName = 'NotORM';
    
    public function __construct($sessionName = null) {          
        $sessionName = $this->setSessionName($sessionName)
                        ->getSessionName();
        if (!isset($_SESSION[$sessionName])) {
            $_SESSION[$sessionName] = array();
        }
    }
    
    public function setSessionName($sessionName)
    {
        if (!empty($sessionName)) {
            $this->sessionName = $sessionName;
        }
        return $this;
    }
    
    public function getSessionName()
    {
        return $this->sessionName;
    }

    public function load($key) 
    {
        if (!isset($_SESSION[$this->getSessionName()][$key])) {
            return null;
        }
        return $this->getDataSerialize($_SESSION[$this->getSessionName()][$key]);
    }

    public function save($key, $data) 
    {
        $_SESSION[$this->getSessionName()][$key] = $this->saveDataSerialize($data);
    }
    
    public function clear($key = null) 
    {
        if (empty($key)) {
            $_SESSION[$this->getSessionName()] = array();
            return true;
        }
        if (array_key_exists($key, $_SESSION[$this->getSessionName()])) {
            unset($_SESSION[$this->getSessionName()][$key]);
        }
    }
}