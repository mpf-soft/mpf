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

use mpf\interfaces\TranslatorInterface;
use mpf\translators\Exception;
use mpf\translators\None;

trait TranslatableObjectTrait
{

    /**
     *
     * @var TranslatorInterface;
     */
    private $_translator;
    public $translator = None::class;

    /**
     * Set a new translator. Can be sent as class name or class instance;
     * @param string|TranslatorInterface $class
     * @throws Exception
     */
    public function setTranslator($class)
    {
        if (is_string($class)) {
            $this->_translator = new $class;
        } else {
            $this->_translator = $class;
        }
        if (!is_a($this->_translator, TranslatorInterface::class)) {
            $this->_translator = null;
            throw new Exception("Invalid translator $class!  Must implement " . TranslatorInterface::class . '!');
        }
        $this->translator = $class;
    }

    /**
     * Return instance to translator
     * @return TranslatorInterface
     * @throws Exception
     */
    public function getTranslator(): TranslatorInterface
    {
        if (!$this->_translator) {
            if ($this->translator) {
                $class = $this->translator;
                $this->_translator = $class::get();
                if (!is_a($this->_translator, TranslatorInterface::class)) {
                    $this->_translator = null;
                    $this->translator = '';
                    throw new Exception("Invalid translator $class!  Must implement " . TranslatorInterface::class . '!');
                }
            }
        }
        return $this->_translator;
    }

    /**
     * Translate a piece of text
     *
     * @param string $text
     * @return string
     * @throws Exception
     */
    public function translate(string $text): string
    {
        $translator = $this->getTranslator();
        if (!$translator) {
            return $text;
        }
        return $translator->t($text, get_class($this));
    }

}
