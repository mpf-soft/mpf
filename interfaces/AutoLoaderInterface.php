<?php

/**
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

namespace mpf\interfaces;

/**
 * Used to implement most used methods to autoload a new class;
 *
 * @author Mirel Nicu Mitache <mirel.mitache@gmail.com>
 * @package MPF Framework
 * @link    http://www.mpfframework.com
 * @category core package
 * @version 1.0
 * @since MPF Framework Version 1
 * @copyright Copyright &copy; 2011 Mirel Mitache 
 * @license  http://www.mpfframework.com/licence
 */
interface AutoLoaderInterface {

    /**
     * This method will the path for a class name. Will return the partial path
     * from libs folder;
     * @param string $name Name of class + namespace;
     * @return string
     */
    public function path($name);

    /**
     * Will include file required for class name;
     * @param string $name Name of class + namespace;
     * @return string
     */
    public function load($name);

    /**
     * Add option to select it from everywhere. If config it's set then it will
     * create a new instance with the selected configuration;
     * @param string[] $config
     * @return \mpf\interfaces\AutoLoaderInterface
     */
    public static function get($config = array());
    
    /**
     * Return last registered autoloader object
     * @return \mpf\interfaces\AutoLoaderInterface
     */
    public static function getLastRegistered();
    
    /**
     * Register this instance as a autoloader;
     */
    public function register();
}
