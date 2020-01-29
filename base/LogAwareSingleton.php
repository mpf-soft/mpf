<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 03.03.2016
 * Time: 13:11
 */

namespace mpf\base;


class LogAwareSingleton extends LogAwareObject {

    private static $_instances = [];

    /**
     * @param array $config
     * @return static
     */
    public static function get($config = [])
    {
        $c = md5(json_encode($config));
        $class = static::class;
        $key = $class.$c;
        if (!isset(self::$_instances[$key])) {
            self::$_instances[$key] = new $class($config);
        }
        return self::$_instances[$key];
    }
}