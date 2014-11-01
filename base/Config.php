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

use mpf\WebApp;

class Config {

    /**
     *
     * @var string[]
     */
    private $options = array();

    /**
     *
     * @var Config
     */
    private static $instance;

    public function __construct($options = null) {
        if (null === $options)
            return;
        if (is_string($options)) {
            $this->options = include($options);
        } elseif (is_array($options)) {
            $this->options = $options;
        }
        self::$instance = $this;
    }

    /**
     * Return a link to current config class;
     * @return Config
     */
    public static function get() {
        if (!self::$instance)
            return new Config();
        return self::$instance;
    }

    /**
     * Returns config for a specific class. It will also check class parents and
     * add extra config for each of them.
     * @param string $className
     * @return string[]
     */
    public function forClass($className) {
        $parents = $this->getParents($className);
        $finalConfig = array();
        foreach ($parents as $parent) { // child will overwrite parent config for selected options;
            if (isset($this->options[$parent]) && count($this->options[$parent])) {
                foreach ($this->options[$parent] as $name => $option)
                    $finalConfig[$name] = $option;
            }
        }
        return $finalConfig;
    }

    /**
     * A small cache for same function (example models)
     * @var string[]
     */
    private $_parents = array();

    /**
     * Get all parent class and implemented interfaces for selected class;
     * @param string $name
     * @return string[]
     */
    private function getParents($name) {
        if (!isset($this->_parents[$name])) {
            $class = new \ReflectionClass($name);
            $parents = array($name);
            $interfaces = $class->getInterfaceNames();
            while ($class = $class->getParentClass())
                $parents[] = $class->getName();
            $this->_parents[$name] = array_reverse($parents);
            foreach ($interfaces as $int) {
                $this->_parents[$name][] = $int;
            }
        }
        return $this->_parents[$name];
    }

    /**
     * Update config for a specified class.
     * @param string $className
     * @param $values
     * @return $this
     */
    public function set($className, $values) {
        if (!isset($this->options[$className])) {
            $this->options[$className] = array();
        }
        foreach ($values as $k => $v) {
            $this->options[$className][$k] = $v;

        }
        return $this;
    }
}
