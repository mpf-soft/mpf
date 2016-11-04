<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 9/4/14
 * Time: 5:17 PM
 */

namespace mpf\web;


use mpf\base\LogAwareObject;
use mpf\interfaces\CacheInterface;

class Cookie extends LogAwareObject implements CacheInterface {

    /**
     * @var static
     */
    private static $_instance;

    /**
     * @return Cookie
     */
    public static function get() {
        if (!static::$_instance) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    /**
     * Domain used for cookie.
     * @var null
     */
    public $domain = null;

    /**
     * Available only for HTTTP
     * @var bool
     */
    public $httpOnly = true;

    /**
     * Record if value should be secured or not.
     * @var bool
     */
    public $secured = false;

    /**
     * Use hash to check if the value was altered.
     * @var bool
     */
    public $hashed = true;

    /**
     * Salt used to generate verification hash
     * @var string
     */
    public $salt = '123132#!@#3424#@$#25543';

    /**
     * Default days for a cookie value
     * @var int
     */
    public $days = 30;

    /**
     * Default hours for a cookie value
     * @var int
     */
    public $hours = 0;

    /**
     * Default minutes for a cookie value
     * @var int
     */
    public $minutes = 0;

    /**
     * Get cookie value for selected key.
     * @param string $key
     * @return mixed|null
     */
    public function value($key) {
        if (!isset($_COOKIE[$key])) {
            return null;
        }
        if ($this->hashed) {
            list($value, $hash) = unserialize($_COOKIE[$key]);
            if ($hash != hash('sha256', $value . $this->salt)) {
                return null;
            }
        } else {
            $value = $_COOKIE[$key];
        }
        return unserialize($value);
    }

    /**
     * Set a cookie using current parameters.
     * @param string $key
     * @param mixed $value
     * @param null|int $days
     * @param null|int $hours
     * @param null|int $minutes
     * @return bool
     */
    public function set($key, $value, $days = null, $hours = null, $minutes = null) {
        $days = null !== $days ? $days : $this->days;
        $hours = null !== $hours ? $hours : $this->hours;
        $minutes = null !== $minutes ? $minutes : $this->minutes;
        $value = serialize($value);
        if ($this->hashed) {
            $value = serialize(array(
                $value,
                hash('sha256', $value . $this->salt)
            ));
        }
        $domain = ltrim($_SERVER['SERVER_NAME'], "www.");
        return setcookie($key, $value, time() + $minutes * 60 + $hours * 3600 + $days * 24 * 3600, '/', $domain, $this->secured, $this->httpOnly);
    }

    /**
     * Delete a single cookie value
     * @param $key
     */
    public function delete($key){
        $domain = ltrim($_SERVER['SERVER_NAME'], "www.");
        setcookie($key, "", time(), '/', $domain, $this->secured, $this->httpOnly);
    }

    /**
     * Checks if a key exists in cookies.
     * @param $key
     * @return bool
     */
    public function exists($key){
        return isset($_COOKIE[$key]);
    }
} 