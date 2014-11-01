<?php
/**
 * Created by PhpStorm.
 * User: Mirel Mitache
 * Date: 05.10.2014
 * Time: 17:39
 */

namespace mpf\interfaces;


interface CacheInterface extends  LogAwareObjectInterface{
    /**
     * @return static
     */
    public static function get();

    public function value($key);

    public function set($key, $value);

    public function exists($key);

    public function delete($key);
} 