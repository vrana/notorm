<?php

use \NotORM\Cache\Apc;

class ApcTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var Apc
     */
    protected $apc;
    protected $data = array('Tests' => 'tests');
    protected $key = 'testKey';

    public function setUp() {
        $this->apc = new Apc;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetLifetimeStringException() {
        $this->apc->setLifetime('string');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetLifetimeNegativeException() {
        $this->apc->setLifetime(-3600);
    }
    
    public function testSetLifetime() {
        $lifetime = 300;
        $this->apc->setLifetime($lifetime);
        $this->assertEquals($lifetime, $this->apc->getLifeTime());
    }
    
    public function testLoadNull() {
        $null = $this->apc->load(time());
        $this->assertEmpty($null);
    }
    
    public function testSaveStorage() {        
        $return = $this->apc->save($this->key, $this->data);
        $this->assertTrue($return);
    }
    
    /**
     * @depends testSaveStorage
     */
    public function testGetData() {
        $this->assertEquals($this->data, $this->apc->load($this->key));
    }

}
