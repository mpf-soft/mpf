<?php

namespace mpf\base;
/**
 * This is the basic class that all the other framework components, and most of the application classes extend.
 *
 * There are few exceptions like the {@class:\mpf\base\Config} and  {@class:\mpf\datasources\sql\PDOConnection}.
 *
 */
class Object {

    /**
     * This method takes care that config list is applied to object. Config list must be a list that contains class attributes as keys and the value as list values.
     *
     * Example:
     *
     * [php]
     * class Test extends \mpf\base\Object{
     *     public $name = 'Mirel';
     * }
     *
     * $test = new Test();
     * echo $test->name; // result in :Mirel
     * $testConfig new Test(['name' => 'Nicu']);
     * echo $testConfig->name; // results in Nicu
     * [/php]
     *
     * This method will also call {@method:\mpf\base\Object::init()} method that can be overwritten for each class and used as a construct.
     * @param string[] $config A list of attribute->value pairs to be applied to this object
     */
    public function __construct($config = []) {
        if (!is_a($this, 'mpf\\interfaces\\AutoLoaderInterface')) {
            $this->applyConfig(Config::get()->forClass(get_called_class()));
        }
        $this->applyConfig($config);
        $this->init($config);
    }

    /**
     * This methods applies the selected config list to the class. Is called by {@method:\mpf\base\Object::__construct()) when the class is initialized to apply the config sent as param and the config found in database.
     *
     * It can also be called at any time to apply more configurations.
     *
     * Call example:
     *
     * [php]
     * class User extends \mpf\base\Object{
     *    public $name;
     *    public $age;
     * }
     *
     * $dan = new User(['name' => 'Dan']);
     * $dan->applyConfig(['age' => 34]);
     *
     * echo "User {$dan->name} is {$dan->age} years old!"; // User Dan is 34 years old!
     * [/php]
     * @param string[] $config A list of attribute->value pairs to be applied to this object
     */
    public function applyConfig($config) {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * This method can be overwritten. It will be called by {@method:\mpf\base\Object::__construct()} every time the class is initiated. It will be called after the configuration for the class is applied, also, the configuration sent as a parameter to {@method:\mpf\base\Object::__construct()} will also be sent as a parameter here.
     *
     * Example:
     *
     * [php]
     * class Test extends \mpf\base\Object{
     *    protected function init($config){
     *        echo "Class Test loaded!";
     *        return parent::init();
     *    }
     * }
     *
     * $t = new Test();
     * // will print: "Class Test loaded!"
     * [/php]
     *
     * @param string[] $config Config values that were sent when object was initiated;
     */
    protected function init($config) {

    }

    /**
     * Get name of the called class as a string.
     * @return string Full class name
     */
    public static function className() {
        return get_called_class();
    }

}
