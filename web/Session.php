<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 18.09.2014
 * Time: 10:53
 */

namespace mpf\web;


use mpf\base\LogAwareObject;
use mpf\interfaces\CacheInterface;

class Session extends LogAwareObject implements CacheInterface {

    /**
     * Records if session has been already started or not.
     * @var bool
     */
    private static $sessionStarted = false;
    /**
     * @var static
     */
    private static $_instance;

    /**
     * @return Session
     */
    public static function get() {
        if (!static::$_instance) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    /**
     * Get session ID
     * @param null|string $newId
     * @return string
     */
    public function id($newId = null){
        return $newId?session_id($newId):session_id();
    }

    protected function init($config = array()) {
        if (!self::$sessionStarted) {
            session_start();
            self::$sessionStarted = true;
        }
        return parent::init($config);
    }

    /**
     * Get value of selected key.
     * @param string $key
     */
    public function value($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    /**
     * Set a value for specific key.
     * @param string $key
     * @param $value
     * @return $this
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
        return $this;
    }

    /**
     * Updates a single item from list. If that list is not found then it will have the selected default value that will
     * then be updated with the selected options.
     * @param string $key
     * @param string $itemKey
     * @param $itemValue
     * @param array $listDefaultValue
     * @return $this
     */
    public function updateListItem($key, $itemKey, $itemValue, $listDefaultValue = array()) {
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = $listDefaultValue;
        }
        $_SESSION[$key][$itemKey] = $itemValue;
        return $this;
    }

    /**
     * Delete selected key from session.
     * @param string $key
     * @return $this
     */
    public function delete($key) {
        unset($_SESSION[$key]);
        return $this;
    }

    /**
     * Check if a selected key exists in session.
     * @param string $key
     * @return bool
     */
    public function exists($key) {
        return array_key_exists($key, $_SESSION);
    }
} 