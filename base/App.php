<?php

/**
 * @author Mirel Nicu Mitache <mirel.mitache@gmail.com>
 * @package MPF Framework
 * @link    http://www.mpfframework.com
 * @category core package
 * @version 1.0
 * @since MPF Framework Version 1.0
 * @copyright Copyright &copy; 2011 Mirel Mitache
 * @license  http://www.mpfframework.com/licence
 *
 * This file is part of MPF Framework.
 *
 * MPF Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MPF Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MPF Framework.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace mpf\base;

abstract class App extends LogAwareObject {

    /**
     * Set from index to measure total time
     * @var int
     */
    public $startTime;

    /**
     * String title of current app.
     * @var string
     */
    public $title = 'MPF App';

    /**
     * Class name for cache engine. Must have implement CacheInterface
     * @var string
     */
    public $cacheEngineClass;

    /**
     * Link to composer autoload class
     * @var \Composer\Autoload\ClassLoader
     */
    protected  $autoload;


    /**
     * Recors current app instance;
     * @var \mpf\base\App
     */
    private static $_instance;

    /**
     * Starts a new application using selected configuration;
     *
     * @param string[] $config
     * @return static
     */
    public static function run($config = array()) {
        try {
            $class = get_called_class();
            self::$_instance = new $class($config);
            self::$_instance->start();
            return self::$_instance;
        } catch (\ErrorException $ex) {
            if (in_array($ex->getSeverity(), array(E_WARNING, E_USER_WARNING)))
                self::get()->warning($ex->getMessage(), array(
                    'File' => $ex->getFile(),
                    'Line' => $ex->getLine(),
                    'Type' => $ex->getSeverity(),
                    'Trace' => $ex->getTraceAsString()
                ));
            elseif (in_array($ex->getSeverity(), array(E_NOTICE, E_USER_NOTICE, E_USER_DEPRECATED)))
                self::get()->notice($ex->getMessage(), array(
                    'File' => $ex->getFile(),
                    'Line' => $ex->getLine(),
                    'Type' => $ex->getSeverity(),
                    'Trace' => $ex->getTraceAsString()
                ));
            else
                self::get()->error($ex->getMessage(), array(
                    'File' => $ex->getFile(),
                    'Line' => $ex->getLine(),
                    'Type' => $ex->getSeverity(),
                    'Trace' => $ex->getTraceAsString()
                ));
        } catch (\Exception $ex) {
            self::get()->error($ex->getMessage(), array('exception' => $ex));
        }
    }

    /**
     * Fast access to active Application;
     * @return static
     */
    public static function get() {
        if (!self::$_instance)
            return self::run();
        return self::$_instance;
    }

    /**
     * This method is to be executed when the new application it's instantiated.
     * It can have code that needs to be executed in the beginning for the specific
     * application type;
     */
    abstract protected function start();

    /**
     * Shortcut to Sql database connection;
     * @param string[] $options
     * @return \mpf\datasources\sql\PDOConnection
     */
    public function sql($options = array()) {
        return \mpf\datasources\sql\PDOConnection::get($options);
    }

    /**
     * Shortcut to Redis database connection
     * @param string[] $options
     * @return \Predis\Client
     */
    public function redis($options = array()) {
        return \mpf\datasources\redis\Connection::get($options);
    }

    /**
     * Shortcut to Mongodb database connection
     * @param string[] $options
     * @return \mpf\datasources\mongodb\Connection
     */
    public function mongodb($options = array()) {
        return \mpf\datasources\mongodb\Connection::get($options);
    }

    /**
     * Shortcut to Redis database connection
     * @param string[] $options
     * @return \mpf\datasources\couchbase\Connection
     */
    public function couchbase($options = array()) {
        return \mpf\datasources\couchbase\Connection::get($options);
    }

    /**
     * Shortcut to Mongodb database connection
     * @param string[] $options
     * @return \mpf\datasources\elasticsearch\Connection
     */
    public function elasticsearch($options = array()) {
        return \mpf\datasources\elasticsearch\Connection::get($options);
    }

    /**
     * Get value for selected cache key. If cache engine is not set it will return null.
     * @param $key
     * @return mixed
     */
    public function cacheValue($key) {
        return $this->cache()?$this->cache()->value($key):null;
    }

    /**
     * Check if selected key is saved in cache. If no cache engine is set it will return false.
     * @param string $key
     * @return mixed|bool
     */
    public function cacheExists($key) {
        return $this->cache()?$this->cache()->exists($key):false;
    }

    /**
     * Set a value for selected key in the selected cache object.
     * @param string $key
     * @param $value
     * @return null|bool
     */
    public function cacheSet($key, $value) {
        return $this->cache()?$this->cache()->set($key, $value):null;
    }

    /**
     * Get an instance of selected Cache object or null if none is set;
     * @return \mpf\interfaces\CacheInterface|null
     */
    public function cache(){
        if (!$this->cacheEngineClass){
            return null;
        }
        $class = $this->cacheEngineClass;
        return $class::get();
    }

    /**
     * Link to composer autoload class
     * @return \Composer\Autoload\ClassLoader
     */
    public function autoload(){
        return $this->autoload;
    }


}
