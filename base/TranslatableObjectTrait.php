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

use mpf\translators\Exception;

trait TranslatableObjectTrait {

    /**
     *
     * @var \mpf\translators\Interface;
     */
    private $_translator;
    public $translator = '\\mpf\\translators\\None';

    /**
     * Set a new translator. Can be sent as class name or class instance;
     * @param string|\mpf\interfaces\TranslatorInterface $class
     * @throws \mpf\translators\Exception
     */
    public function setTranslator($class) {
        if (is_string($class)) {
            $this->_translator = new $class;
        } else {
            $this->_translator = $class;
        }
        if (!is_a($this->_translator, '\\mpf\\interfaces\\TranslatorInterface')) {
            $this->_translator = null;
            throw new Exception("Invalid translator $class!  Must implement \\mpf\\interfaces\\TranslatorInterface!");
        }
        $this->translator = $class;
    }

    /**
     * Return instance to translator
     * @return \mpf\interfaces\TranslatorInterface
     * @throws \mpf\translators\Exception
     */
    public function getTranslator() {
        if (!$this->_translator) {
            if ($this->translator) {
                $class = $this->translator;
                $this->_translator = $class::get();
                if (!is_a($this->_translator, '\\mpf\\interfaces\\TranslatorInterface')) {
                    $this->_translator = null;
                    $this->translator = '';
                    throw new Exception("Invalid translator $class!  Must implement \\mpf\\interfaces\\TranslatorInterface!");
                }
            }
        }
        return $this->_translator;
    }

    public function translate($text) {
        if (!$this->_translator)
            return $text;
        return $this->_translator->t($text, get_class($this));
    }

}
