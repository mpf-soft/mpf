<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 9/16/14
 * Time: 2:57 PM
 */

namespace mpf\translators;


use mpf\base\MPFObject;
use mpf\interfaces\TranslatorInterface;

class Redis extends MPFObject implements TranslatorInterface {
    /**
     * @var Redis[string]
     */
    private static $instance;

    /**
     * Key in redis for translations. Value must be a set.
     * @var string
     */
    public $translationsKey = 'translations:{language}';

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
} 