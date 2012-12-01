<?php
namespace NotORM\Cache;
use NotORM\Cache\Memcache;
class MemcacheTest extends \PHPUnit_Framework_TestCase
{
    protected $memcache;
    protected $data = array(
        'host' => '10.1.1.3',        
        );
    public function setUp() 
    {
        $this->memcache =  new Memcache;        
    }
    
    public function testInstancieMemcache()
    {
       $this->assertInstanceOf('NotOrm\Cache\Memcache', $this->memcache);
    }
    
    public function testAddServer()
    {
        $returnServer = $this->memcache->addServer($this->data);        
        $this->assertTrue($returnServer);
    }
    /**
     * @depends testAddServer
     */
    public function testSetData()
    {
        $this->memcache->addServer($this->data);
        $save = $this->memcache->save('teste', $this->data);
        $this->assertTrue($save);
    }
    /**
     * @depends testSetData
     */
    public function testGetData()
    {
        $this->memcache->addServer($this->data);
        $this->assertEquals($this->data, $this->memcache->load('teste'));
    }
    /**
     * @depends testGetData
     */
    public function testClearAll()
    {
        $this->memcache->addServer($this->data);
        $this->memcache->clear();
        $this->assertEmpty($this->memcache->load('teste'));
    }
    
    public function testClearDataForKey()
    {
        $this->memcache->addServer($this->data);
        $this->memcache->save('teste2', $this->data);
        $this->assertEquals($this->data, $this->memcache->load('teste2'));
        $this->memcache->clear('teste2');
        $this->assertEmpty($this->memcache->load('teste2'));
    }
}