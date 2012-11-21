<?php

namespace NotORM;

abstract class NotORMAbstract 
{
    protected $connection, $driver, $structure, $cache;
    protected $notORM, $table, $primary, $rows, $referenced = array();

    protected $debug = false;
    protected $freeze = false;
    protected $rowClass = '\NotORM\Row';

    protected function access($key, $delete = false) 
    {
    }
}


