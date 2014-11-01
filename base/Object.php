<?php

namespace mpf\base;

class Object {

    /**
     * This config options will override the options from main config
     * @param string[] $config
     */
    public function __construct($config = array()) {
        if (!is_a($this, 'mpf\\interfaces\\AutoLoaderInterface')) {
            $this->applyConfig(Config::get()->forClass(get_called_class()));
        }
        $this->applyConfig($config);
        $this->init($config);
    }

    public function applyConfig($config = array()) {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Can be extended to execute a code when the class it's created.
     * By default, Object class doesn't include any code in this method.
     * @param string[] $config Config values that were sent when object was initiated;
     */
    protected function init($config = array()) {
        
    }

    /**
     * Get name of the called class
     * @return string
     */
    public static function className(){
        return get_called_class();
    }

}
