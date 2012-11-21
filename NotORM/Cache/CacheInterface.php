<?php

namespace NotORM\Cache;

interface CacheInterface
{
    /** Load stored data
     * @param string
     * @return mixed or null if not found
     */
    public function load($key);

    /** Save data
     * @param string
     * @param mixed
     * @return null
     */
    public function save($key, $data);
}