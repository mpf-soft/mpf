<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 22.02.2016
 * Time: 11:53
 */

namespace mpf\base;


class Singleton extends Object {

    private static $_instances = [];

    /**
     * @param array $config
     * @return static
     */
    public static function get($config = []) {
        $c = md5(json_encode($config));
        $class = get_called_class();
        if (!isset(self::$_instances[$class . $c]))
            self::$_instances[$class . $c] = new $class($config);
        return self::$_instances[$class . $c];
    }
}