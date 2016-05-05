<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 29.10.2014
 * Time: 11:56
 */

namespace mpf\datasources\redis;


use mpf\base\LogAwareObject;
use Predis\Client;

class Connection extends LogAwareObject{

    private static $instances = array();

    /**
     * Predis parameters
     * @var
     */
    public $parameters;

    /**
     * Predis options
     * @var
     */
    public $options;

    /**
     * @var \Predis\Client
     */
    public $predis;

    /**
     * @param array $config
     * @return \Predis\Client
     */
    public static function get($config = []){
        $key = md5(serialize($config));
        if (!isset(self::$instances[$key])){
            self::$instances[$key] = new static($config);
        }
        return self::$instances[$key]->predis;
    }


    public function init($config){
        $this->predis = new Client($this->parameters, $this->options);
        return parent::info($config);
    }
} 