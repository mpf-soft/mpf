<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 9/16/14
 * Time: 2:48 PM
 */

namespace mpf\translators;


use mpf\base\LogAwareObject;
use mpf\interfaces\TranslatorInterface;

class ArrayFile extends LogAwareObject implements TranslatorInterface {
    /**
     * @var ArrayFile[string]
     */
    private static $instance;

    protected $translations = array();

    /**
     * Path to translation file. If it starts with / or {letter}:\ it will use it as full path, if not it will
     * use this as relative to app folder.
     * {language} can be used and it will be replaced by the actual selected language.
     * @var string
     */
    public $fileName = 'config/translations/{language}.php';

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
        $file = str_replace('{language}', $language, $this->fileName);
        if ('/' != $file[0] && (':\\' != substr($file, 1, 2))) { // it is a relative path
            $file = APP_ROOT . $file;
        }

        $this->translations = include($file);
        $this->debug('Language: ' . $language . ' (' . count($this->translations) . ' translations)! File: ' . $file);
    }

    public function t($text, $class = null) {
        return ($class && isset($this->translations[$class][$text])) ? $this->translations[$class][$text] : (isset($this->translations[$text]) ? $this->translations[$text] : $text);
    }
} 