<?php

namespace mpf\test;

define('LIBS_FOLDER', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
if (class_exists('\mpf\base\AutoLoader', false)) {
    require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR . 'AutoLoader.php';
}

/**
 * Provides a base test class for ensuring compliance with the LoggerInterface
 *
 * Implementors can extend the class and implement abstract methods to run this as part of their test suite
 */
class AutoLoader extends \PHPUnit_Framework_TestCase {

    /**
     * @return \mpf\interfaces\AutoLoaderInterface
     */
    public function getAutoLoader($config = array()) {
        return \mpf\base\AutoLoader::get($config);
    }

    public function testImplements() {
        $this->assertInstanceOf('\mpf\interfaces\AutoLoaderInterface', $this->getAutoLoader());
    }

    public function testPath() {
        $tests=  array(
            '\mpf\base\TestClass' => LIBS_FOLDER . 'mpf/base/TestClass.php',
            '\app\Test\MyClass' => APP_ROOT .'Test/MyClass.php',
            '/invalid' => '',
            'invalid' => ''
        );
        foreach ($tests as $class=>$path){
            $this->assertEquals($path, $this->getAutoLoader()->path($class));
        }
    }

    public function testMultiSlash() {
        $tests=  array(
            '\mpf\base\\TestClass' => LIBS_FOLDER . 'mpf/base/TestClass.php',
            '\app\\Test\MyClass' => APP_ROOT .'Test/MyClass.php',
            '/invalid' => '',
            'invalid' => ''
        );
        foreach ($tests as $class=>$path){
            $this->assertEquals($path, $this->getAutoLoader()->path($class));
        }
    }

}
