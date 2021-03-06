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

/**
 * Extends any helper classes.
 *
 * It contains a single method that returns the latest instance of the called classed with the specified config options.
 *
 * Example:
 *
 * [php]class MyHelper extends Helper{
 *    protected $format = 'Y-m-d H:i:s'; // can be changed from global config or class config when called;
 *    public function getFormatedDate($time = null){
 *        return date($this->format, $time?:time());
 *    }
 * }
 *
 * echo "Current Time is : " . MyHelper::get()->getFormatedDate();
 * // or with custom format:
 *
 * echo "Current Time is: " . MyHelper::get(['format'=>'Ymd H:i:s'])->getFormatedDate();
 * [/php]
 *
 */
class Helper extends TranslatableObject {

    /**
     * @var Helper[]
     */
    private static $_instances = [];

    /**
     * Return a instance for helper.
     *
     * @param array $config
     * @return static
     */
    public static function get($config = []): Helper
    {
        $key = md5(static::class . serialize($config));
        if (!isset(self::$_instances[$key])) {
            self::$_instances[$key] = new static($config);
        }
        return self::$_instances[$key];
    }

}
