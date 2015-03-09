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

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Object.php';
require_once LIBS_FOLDER . 'mpf' . DIRECTORY_SEPARATOR . 'interfaces' . DIRECTORY_SEPARATOR . 'AutoLoaderInterface.php';

/**
 * Class AutoLoader
 *
 * An example of a general-purpose implementation that includes the optional
 * functionality of allowing multiple base directories for a single namespace
 * prefix.
 *
 * Given a foo-bar package of classes in the file system at the following
 * paths ...
 *
 *     /path/to/packages/foo-bar/
 *         src/
 *             Baz.php             # Foo\Bar\Baz
 *             Qux/
 *                 Quux.php        # Foo\Bar\Qux\Quux
 *         tests/
 *             BazTest.php         # Foo\Bar\BazTest
 *             Qux/
 *                 QuuxTest.php    # Foo\Bar\Qux\QuuxTest
 *
 * ... add the path to the class files for the \Foo\Bar\ namespace prefix
 * as follows:
 *
 *      <?php
 *      // instantiate the loader
 *      $loader = new \Example\Psr4AutoloaderClass;
 *
 *      // register the autoloader
 *      $loader->register();
 *
 *      // register the base directories for the namespace prefix
 *      $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/src');
 *      $loader->addNamespace('Foo\Bar', '/path/to/packages/foo-bar/tests');
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\Quux class from /path/to/packages/foo-bar/src/Qux/Quux.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\Quux;
 *
 * The following line would cause the autoloader to attempt to load the
 * \Foo\Bar\Qux\QuuxTest class from /path/to/packages/foo-bar/tests/Qux/QuuxTest.php:
 *
 *      <?php
 *      new \Foo\Bar\Qux\QuuxTest;
 *
 * This class will only be used if case that the framework is installed without the composer. In case that composer is
 * used to maintain packages that it will also take care of the autoload for all classes.
 * @package mpf\base
 */
class AutoLoader extends Object implements \mpf\interfaces\AutoLoaderInterface {

    /**
     * Keeps a link to last instantiated AutoLoader class.
     * @var AutoLoader
     */
    private static $lastRegistered;

    /**
     * An associative array where the key is a namespace prefix and the value
     * is an array of base directories for classes in that namespace.
     *
     * @var array
     */
    public $prefixes = array();

    /**
     * Will check if file exists or not before including it. For a faster execution
     * this can be set to false but a notice will be generated if file doesn't exists;
     * @var boolean
     */
    public $fileExists = false;

    /**
     * This key is used as base namespace for all classes from developer's project.
     * Examples:
     * \app\controllers\Home
     * \app\models\User
     * \app\components\Controller
     */
    const APP_DEVELOPER_VENDORKEY = 'app';

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix The namespace prefix.
     * @param string $base_dir A base directory for class files in the
     * namespace.
     * @param bool $prepend If true, prepend the base directory to the stack
     * instead of appending it; this causes it to be searched first rather
     * than last.
     * @return void
     */
    public function addNamespace($prefix, $base_dir, $prepend = false) {
        // normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';

        // normalize the base directory with a trailing separator
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';

        // initialize the namespace prefix array
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        // retain the base directory for the namespace prefix
        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            array_push($this->prefixes[$prefix], $base_dir);
        }
    }

    /**
     * Will include file required for class name;
     * @param string $name Name of class + namespace;
     * @return string
     */
    public function load($name) {
        if (null === ($path = $this->path($name)))
            return;
        if ($this->fileExists && (!file_exists($path))) {
            return;
        }
        include_once $path;
    }

    /**
     * This method will the path for a class name. Will return the partial path
     * from libs folder;
     * @param string $name Name of class + namespace;
     * @param bool $folder If is set as folder .php extension is not added
     * @return string
     */
    public function path($name, $folder = false) {

        // the current namespace prefix
        $prefix = $name;

        // work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while (false !== $pos = strrpos($prefix, '\\')) {

            // retain the trailing namespace separator in the prefix
            $prefix = substr($name, 0, $pos + 1);

            // the rest is the relative class name
            $relative_class = substr($name, $pos + 1);

            // try to load a mapped file for the prefix and relative class
            $mapped_file = $this->loadMappedFile($prefix, $relative_class, $folder);
            if ($mapped_file) {
                return $mapped_file;
            }

            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = rtrim($prefix, '\\');
        }

        // never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix The namespace prefix.
     * @param string $relative_class The relative class name.
     * @param bool $folder If is a folder '.php' won't be added.
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relative_class, $folder) {
        // are there any base directories for this namespace prefix?
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        // look through base directories for this namespace prefix
        foreach ($this->prefixes[$prefix] as $base_dir) {

            // replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir
                . str_replace('\\', '/', $relative_class)
                . ($folder ? '' : '.php');

            // if the mapped file exists, require it
            if ($this->requireFile($file)) {
                // yes, we're done
                return $file;
            }
        }

        // never found it
        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile($file) {
        if (!$this->fileExists || file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }

    /**
     *
     * @var \mpf\base\AutoLoader[]
     */
    private static $_self = array();

    /**
     * This method is used to get an instance of this class from anywhere without having to initialize the class each time.
     * It will offer different instances for each config.
     * @param string[] $config Extra config options that can be specified for each class
     * @return \mpf\base\AutoLoader
     */
    public static function get($config = []) {
        if (isset(self::$_self[$hash = md5(serialize($config))]))
            return self::$_self[$hash];
        return new \mpf\base\AutoLoader($config);
    }

    /**
     * Get last registered autoloader. In most of the cases only one will be registered.
     * @return \mpf\base\AutoLoader
     */
    public static function getLastRegistered() {
        return self::$lastRegistered;
    }

    /**
     * When initiating set value for self::$_self; so that it can be called from ::get();
     * Multiple values will be set for that, one for each config variant that is initiated.
     * @param string[] $config Config values that were used when the object was instantiated.
     */
    protected function init($config = []) {
        $this->addNamespace(self::APP_DEVELOPER_VENDORKEY, APP_ROOT);
        self::$_self[md5(serialize($config))] = $this;
        return parent::init();
    }

    /**
     * Register this instance as a autoloader;
     * Multiple autoload classes can be registered and somepackages will register their own autoload class. Is better to
     * not remove this class except if you're using composer to handle the autoload process.
     */
    public function register() {
        self::$lastRegistered = $this;
        return spl_autoload_register(array($this, 'load'));
    }

}
