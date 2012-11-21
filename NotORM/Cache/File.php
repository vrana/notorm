<?php

namespace NotORM\Cache;

class File implements CacheInterface
{
    private $filename, $data = array();

    public function __construct($filename) 
    {
        if (!$this->checkFile($filename)) {
            throw new \InvalidArgumentException("This {$filename} don't readable");
        }
        $this->filename = $filename;
        $this->data = unserialize(@file_get_contents($filename)); // @ - file may not exist
    }

    public function load($key) 
    {
        if (!isset($this->data[$key])) {
            return null;
        }
        return $this->data[$key];
    }

    public function save($key, $data) 
    {
        if (!isset($this->data[$key]) || $this->data[$key] !== $data) {
            $this->data[$key] = $data;
            file_put_contents($this->filename, serialize($this->data), LOCK_EX);
        }
    }
    
    private function checkFile($filename)
    {
        return file_exists($filename) && is_readable($filename);
    }
}


