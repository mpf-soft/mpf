<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 29.10.2014
 * Time: 12:02
 */

namespace mpf\datasources\redis;


use mpf\base\App;
use mpf\base\LogAwareObject;
use mpf\interfaces\CacheInterface;

class Cache extends LogAwareObject implements CacheInterface{

    public $key = ':Cache';

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
        return $this->exists($key)?unserialize(Connection::get()->hget(App::get()->shortName . $this->key, $key)):null;
    }

    public function set($key, $value) {
        return Connection::get()->hset(App::get()->shortName . $this->key, $key, serialize($value));
        // TODO: Implement set() method.
    }

    public function exists($key) {
        return Connection::get()->exists(App::get()->shortName . $this->key) && Connection::get()->hexists(App::get()->shortName . $this->key, $key);
    }

    public function delete($key) {
        return Connection::get()->hdel(App::get()->shortName . $this->key, key);
    }

}