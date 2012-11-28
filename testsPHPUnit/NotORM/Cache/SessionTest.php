<?php

namespace NotORM\Cache;

use \NotORM\Cache\Session;

class SessionTest extends \PHPUnit_Framework_TestCase 
{
    protected $session;
    protected $sessionName = 'TestNotORM';
    protected $key = 'keyTest';
    protected $value = array('NotORM' => "It's simple DBAL");
    public function setUp() 
    {
        $this->session = new Session;        
    }
    
    public function testSessionConstructSessionName()
    {
        $this->session = new Session($this->sessionName);
        $this->assertEquals($this->sessionName, $this->session->getSessionName());
    }
    
    public function testSetSessionNameAndGetSessionName()
    {
        $this->assertInstanceOf('\NotORM\Cache\Session', $this->session->setSessionName($this->sessionName));
        $this->assertEquals($this->sessionName, $this->session->getSessionName());
    }
    
    public function testLoadNull()
    {
        $this->assertEmpty($this->session->load('keyNotExists'));
    }
     
    public function testSaveData()
    {
        
        $this->assertTrue($this->session->save($this->key, $this->value));
    }
       
    public function testGetLoadValue()
    {
        $this->session->save($this->key, $this->value);
        $this->assertEquals($this->value, $this->session->load($this->key));
    }
    
    public function testClearAll()
    {
        $this->session->save($this->key, $this->value);
        $this->session->clear();
        $this->assertEmpty($_SESSION['NotORM']);
    }
    
    public function testClearKey()
    {
        $this->session->save($this->key, $this->value);
        $this->session->clear($this->key);
        $this->assertEmpty($this->session->load($this->key));
    }
}