<?php

/*
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

use mpf\interfaces\TranslatableObjectInterface;

class Widget extends LogAwareObject implements TranslatableObjectInterface {

    use TranslatableObjectTrait;

    private static $_instances = array();

    protected static function prepareForSerialize($array) {
        $res = [];
        foreach ($array as $k=>$v){
            if (is_array($v)){
                $res[$k] = self::prepareForSerialize($v);
            } elseif (is_callable($v)) {
                $res[$k] = print_r($v, true);
            } else {
                $res[$k] = is_object($v) ? print_r($v, true) : $v; // to fix the problem with unserializable objects)
            }
        }
        return $res;
    }

    /**
     * Return instance of called class for specific config
     * @param string [string] $config
     * @return static
     */
    public static function get($config) {
        $key = md5(get_called_class() . serialize(self::prepareForSerialize($config)));
        if (!isset(self::$_instances[$key]))
            self::$_instances[$key] = new static($config);
        return self::$_instances[$key];
    }

    private $_vars = array();

    /**
     * Display a template using full path. It will also assign all variables to it.
     * @param string $filePath
     * @param array|string[string] $variables
     */
    protected function render($filePath, $variables = array()) {
        foreach ($this->_vars as $k => $n) {
            $$k = $n;
        }
        foreach ($variables as $k => $n) {
            $$k = $n;
        }
        require $filePath;
    }

    /**
     * Assign value to viewer
     * @param string $name
     * @param mixed $value
     */
    protected function assign($name, $value) {
        $this->_vars[$name] = $value;
    }

}
