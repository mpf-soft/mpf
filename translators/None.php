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

namespace mpf\translators;

use mpf\base\Object;
use mpf\interfaces\TranslatorInterface;

class None extends Object implements TranslatorInterface {

    /**
     * @var None[string]
     */
    private static $instance;

    /**
     * @param array $config
     * @return TranslatorInterface
     */
    public static function get($config = array()) {
        if (!isset(static::$instance[md5(serialize($config))])) {
            static::$instance[md5(serialize($config))] = new static($config);
        }
        return static::$instance[md5(serialize($config))];
    }


    /**
     * Mockup method! Does nothing!
     * @param string $language
     */
    public function setLanguage($language) {

    }

    /**
     * Returns original text;
     * @param string $text
     * @param string|null $class
     * @return string
     */
    public function t($text, $class = null) {
        return $text;
    }

}
