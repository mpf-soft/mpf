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

use mpf\datasources\redis\Connection;
use mpf\datasources\sql\PDOConnection;

/**
 * App class is extended, by default, by {@class:\mpf\WebApp} and {@class:\mpf\ConsoleApp} and those are the main classes used by the framework to run a website or a terminal application.
 * It can also be extended by a custom class if that's needed to create a specific type of application. Any class that extends this must include `::start()` method that will be called from
 * `run()` method.
 *
 * It is usually instantiated in the index file of the application like this:
 *
 * ## Framework App index file
 *
 * [php]
 * \mpf\ConsoleApp::run([
 *    'startTime' => microtime(true),
 *    'autoload' => $autoload // <- an instance of composer autoload class;
 * ]);
 * [/php]
 *
 * Some classes from the framework also require that 2 constants should be defined in the index before running the main app: `LIBS_FOLDER` and `APP_ROOT`.
 *
 *  - `LIBS_FOLDER`  records the location of `"vendor"` folder created by composer
 *  - `APP_ROOT` records the location of `"php"` folder of the main app, where all php files are found.
 *
 * Those  are used by autoloader to load all classes when needed and find other folders locations.
 *
 * Index file should also define an error handler. All exceptions are already handled by the app automatically by for errors the programmer must handle them.
 *
 * This can be done by something similar to this:
 * [php]
 * set_error_handler(function ($errno, $errstr, $errfile, $errline) {
 *     $severity = 1 * E_ERROR | // change 0 / 1 value to ignore / handle different errors;
 *         1 * E_WARNING |
 *         1 * E_PARSE |
 *         1 * E_NOTICE |
 *         0 * E_CORE_ERROR |
 *         0 * E_CORE_WARNING |
 *         0 * E_COMPILE_ERROR |
 *         0 * E_COMPILE_WARNING |
 *         1 * E_USER_ERROR |
 *         1 * E_USER_WARNING |
 *         1 * E_USER_NOTICE |
 *         0 * E_STRICT |
 *         0 * E_RECOVERABLE_ERROR |
 *         0 * E_DEPRECATED |
 *         0 * E_USER_DEPRECATED;
 *     $ex = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
 *     if (($ex->getSeverity() & $severity) != 0) {
 *         \mpf\ConsoleApp::get()->error($errstr, array(
 *             'File' => $errfile,
 *             'Line' => $errline,
 *             'Number' => $errno,
 *             'Trace' => $ex->getTraceAsString()
 *         ));
 *     }
 * });
 * [/php]
 *
 * ## How to access App class
 *
 * App class can be accessed from anyplace by calling the exact type of app used or by calling the general app. In models and classes that are used by multiple types
 * of apps is better to access the main app. It will return the current instance created in index. For the example written above the following code will return a
 * {@class:\mpf\ConsoleApp} instance: `$inst = \mpf\base\App::get()`.
 *
 * Accessing components from the app is really ways because of this:
 *
 * [php]
 *
 *     // access Sql datasource and run a query:
 *     \mpf\base\App::get()->sql()->Query("SELECT * FROM `something`");
 *
 *     // access redis datasource and read a key:
 *     \mpf\base\App::get()->redis()->get("something");
 * [/php]
 *
 */
abstract class App extends LogAwareObject
{

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
     * To be used by common keys like Redis, Cookies, Session, Files Path, any other Cache system and so on.. anything
     * that can be accessed by multiple websites.
     * @var string
     */
    public $shortName = 'app';

    /**
     * Class name for cache engine. Must have implement CacheInterface
     * @var string
     */
    public $cacheEngineClass;

    /**
     * Link to composer autoload class
     * @var \Composer\Autoload\ClassLoader
     */
    protected $autoload;


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
    public static function run($config = [])
    {
        try {
            $class = get_called_class();
            self::$_instance = new $class($config);
            self::$_instance->start();
            return self::$_instance;
        } catch (\ErrorException $ex) {
            if (in_array($ex->getSeverity(), array(E_WARNING, E_USER_WARNING)))
                self::get()->warning($ex->getMessage(), [
                    'File' => $ex->getFile(),
                    'Line' => $ex->getLine(),
                    'Type' => $ex->getSeverity(),
                    'Trace' => $ex->getTraceAsString(),
                    'Class' => get_class($ex)
                ]);
            elseif (in_array($ex->getSeverity(), array(E_NOTICE, E_USER_NOTICE, E_USER_DEPRECATED)))
                self::get()->notice($ex->getMessage(), [
                    'File' => $ex->getFile(),
                    'Line' => $ex->getLine(),
                    'Type' => $ex->getSeverity(),
                    'Trace' => $ex->getTraceAsString(),
                    'Class' => get_class($ex)
                ]);
            else
                self::get()->error($ex->getMessage(), [
                    'File' => $ex->getFile(),
                    'Line' => $ex->getLine(),
                    'Type' => $ex->getSeverity(),
                    'Trace' => $ex->getTraceAsString(),
                    'Class' => get_class($ex)
                ]);
        } catch (\Exception $ex) {
            self::get()->error($ex->getMessage(), array('exception' => $ex));
        }
    }

    /**
     * Fast access to active Application;
     * @return static
     */
    public static function get()
    {
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
    public function sql($options = [])
    {
        return PDOConnection::get($options);
    }

    /**
     * Shortcut to Redis database connection
     * @param string[] $options
     * @return \Predis\Client
     */
    public function redis($options = array())
    {
        return Connection::get($options);
    }


    /*
     * Shortcut to Mongodb database connection
     * @param string[] $options
     * @return \mpf\datasources\mongodb\Connection
     *//*
    public function mongodb($options = array())
    {
        trigger_error("MONGODB CONNECTION NOT IMPLEMENTED YET!");
        return \mpf\datasources\mongodb\Connection::get($options);
    }*/

    /*
     * Shortcut to Redis database connection
     * @param string[] $options
     * @return \mpf\datasources\couchbase\Connection
     *//*
    public function couchbase($options = array()) {
        trigger_error("COUCHBASE CONNECTION NOT IMPLEMENTED YET!");
        return \mpf\datasources\couchbase\Connection::get($options);
    }*/

    /*
     * Shortcut to Mongodb database connection
     * @param string[] $options
     * @return \mpf\datasources\elasticsearch\Connection
     *//*
    public function elasticsearch($options = array()) {
        trigger_error("ELASTICSEARCH CONNECTION NOT IMPLEMENTED YET!");
        return \mpf\datasources\elasticsearch\Connection::get($options);
    }
*/
    /**
     * Get value for selected cache key. If cache engine is not set it will return null.
     * @param $key
     * @return mixed
     */
    public function cacheValue($key)
    {
        return $this->cache() ? $this->cache()->value($key) : null;
    }

    /**
     * Check if selected key is saved in cache. If no cache engine is set it will return false.
     * @param string $key
     * @return mixed|bool
     */
    public function cacheExists($key)
    {
        return $this->cache() ? $this->cache()->exists($key) : false;
    }

    /**
     * Set a value for selected key in the selected cache object.
     * @param string $key
     * @param $value
     * @return null|bool
     */
    public function cacheSet($key, $value)
    {
        return $this->cache() ? $this->cache()->set($key, $value) : null;
    }

    /**
     * Get an instance of selected Cache object or null if none is set;
     * @return \mpf\interfaces\CacheInterface|null
     */
    public function cache()
    {
        if (!$this->cacheEngineClass) {
            return null;
        }
        $class = $this->cacheEngineClass;
        return $class::get();
    }

    /**
     * Link to composer autoload class
     * @return \Composer\Autoload\ClassLoader
     */
    public function autoload()
    {
        return $this->autoload;
    }


}
