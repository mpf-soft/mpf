<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 18.09.2014
 * Time: 16:35
 */

namespace mpf\test;

define('LIBS_FOLDER', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
require_once LIBS_FOLDER . 'mpf' . DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR . 'AutoLoader.php';

use mpf\base\AutoLoader;
use mpf\web\request\HTML;

AutoLoader::get()->register();


class HtmlRequest extends \PHPUnit_Framework_TestCase {
    /**
     * @return \mpf\web\request\HTML
     */
    public function getHTMLRequest($config = array()) {
        return HTML::get($config);
    }

    public function testParseURL() {
        $test = array();
        foreach ($test as $url => $result) {
            $this->assertEquals($result, $this->getHTMLRequest()->createURL($params['controller'], $params['action'], $params['params'], $params['module']));
        }
    }

    public function testCreateURL() {
        $test = array();
        foreach ($test as $result => $params) {
            $this->assertEquals($result, $this->getHTMLRequest()->createURL($params['controller'], $params['action'], $params['params'], $params['module']));
        }
    }
} 