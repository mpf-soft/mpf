<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 29.10.2014
 * Time: 12:02
 */

namespace mpf\datasources\redis;


use mpf\base\LogAwareObject;
use mpf\interfaces\CacheInterface;

class Cache extends LogAwareObject implements CacheInterface{

    public $key = 'App:Cache';

    private static $instance;

    /**
     * @return static
     */
    public static function get() {
        if (!self::$instance){
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function value($key) {
        return $this->exists($key)?unserialize(Connection::get()->hget($this->key, $key)):null;
    }

    public function set($key, $value) {
        return Connection::get()->hset($this->key, $key, serialize($value));
        // TODO: Implement set() method.
    }

    public function exists($key) {
        return Connection::get()->exists($this->key) && Connection::get()->hexists($this->key, $key);
    }

    public function delete($key) {
        return Connection::get()->hdel($this->prefix, key);
    }

}