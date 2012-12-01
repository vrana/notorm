<?php

namespace NotORM\Cache;
use NotORM\Cache\File;
class FileTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheDir = 'cache_dir';
    /**
     *
     * @var File
     */
    protected $cache;
    protected $data = array('tests' => 'TestsPHPUnit');
            
    public function setUp() 
    {
        mkdir($this->cacheDir);
        $this->cache = new File($this->cacheDir);
    }
    
    public function tearDown()
    {
        foreach (new \RecursiveDirectoryIterator($this->cacheDir) as $file) {
            if ($file->isFile()) {
                unlink($file);
            } elseif ($file->isDir()) {
                $name = $file->getPathname();
                $filesOfDir = glob($name);
                if (empty($filesOfDir)) {
                    rmdir($file);
                }
            }
        }
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testDirIsNotExists()
    {
        new File('noDir/');
    }
    
    public function testSetDirCache()
    {
        $this->assertInstanceOf('NotORM\Cache\File' ,$this->cache->setCacheDir($this->cacheDir));
    }
    
    public function testSetData()
    {
        $ret = $this->cache->save('test', $this->data);
        $this->assertTrue($this->cache->save('test', $this->data));
    }
    
    public function testLoadKey()
    {
        $this->cache->save('test', $this->data);
        $this->assertEquals($this->data, $this->cache->load('test'));
    }
}