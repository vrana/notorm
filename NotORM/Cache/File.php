<?php

namespace NotORM\Cache;

class File extends CacheAbstract implements CacheInterface
{
    private $filename;    
    private $cacheDir;

    public function __construct($cacheDir = null) 
    {
        $this->cacheDir = $cacheDir;
        $this->checkDirWrite();
    }
    
    private function checkDirWrite()
    {
         if (!is_writable($this->cacheDir)) {
             throw new \RuntimeException("This {$this->cacheDir} is not write");
         }
    }
    
    public function setCacheDir($dir)
    {           
        $this->cacheDir = $dir;
        $this->checkDirWrite();
        return $this;
    }
    
    public function save($key, $data) 
    {              
        $this->saveMetadata($key);
        $this->filename = $this->cacheDir . $this->getFileName($key);
        return (bool)file_put_contents($this->filename, $this->saveDataSerialize($data), LOCK_EX);        
    }
    
    private function getFileName($key)
    {
        return md5($key . 'NotORM');
    }
    
    private function saveMetadata($key)
    {
        file_put_contents($this->cacheDir . $this->getNameMetadata($key), serialize( array('expire',time() + $this->lifetime)), LOCK_EX);
    }
    
    private function getNameMetadata($key)
    {
        return 'internal-metadata-' . $this->getFileName($key);
    }
    
    public function load($key) 
    {
        if ($this->checkFileExists($key) && $this->checkMetadata($key)) {
            return null;
        }
        return file_get_contents($this->getDataSerialize($this->filename));
    }

    private function checkFileExists($filename, $rename = true)
    {        
        $filename = $this->getFileName($this->cacheDir . $filename);
        if ($rename) {
            $this->filename = $filename;
        }
        return file_exists($filename) && is_readable($filename);
    }        
    
    private function checkMetadata($key) 
    {
        $metadataFile = $this->getNameMetadata($key);
        if ($this->checkFileExists($metadataFile, false)) {
            $metadata = unserialize(file_get_contents($metadataFile));
            if (isset($metadata['expire']) && $metadata['expire'] >= time()) {
                return true;
            }
        }
        return false;
    }
    
    public function clear($key = null)
    {
        if (empty($key)) {
            return $this->clearAll();
        }        
        if ($this->checkFileExists($key)) {
            unlink($this->filename);
            return true;
        }
        return false;
    }
    
    private function clearAll()
    {
        foreach(new \RecursiveDirectoryIterator($this->cacheDir) as $filename) {
            unlink($filename);
        }
        return true;
    }
    
}


