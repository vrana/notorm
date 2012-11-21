<?php

namespace NotORM\Cache;

class Session implements CacheInterface
{
    public function load($key) 
    {
        if (!isset($_SESSION["NotORM"][$key])) {
            return null;
        }
        return $_SESSION["NotORM"][$key];
    }

    public function save($key, $data) 
    {
        $_SESSION["NotORM"][$key] = $data;
    }
}