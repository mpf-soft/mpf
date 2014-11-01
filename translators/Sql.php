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

/**
 * Description of Sql
 *
 * @author mirel
 */
class Sql extends Object implements TranslatorInterface {
    /**
     * @var Sql[string]
     */
    private static $instance;

    /**
     * Name of the table where the translations are found
     * {language} can be used here and it will be replaced by the actual used language when it will be translated.
     * This way it will allow to save types:
     *   - one table with all languages on separate columns
     *   - multiple tables, one for each language
     * @var string
     */
    public $table = 'translations';
    /**
     * Name of the column that contains the original text
     * @var string
     */
    public $columnText = 'text';
    /**
     * Name of the column that contains translated text.
     * {language} can be used here and it will be replaced by the actual used language when it will be translated.
     * @var string
     */
    public $columnTranslation = 'translation_{language}';

    protected $translations = array();

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

    public function setLanguage($language) {

    }

    public function t($text, $class = null) {
        return $text;
    }

//put your code here
}
