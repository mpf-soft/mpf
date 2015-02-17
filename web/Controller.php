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

namespace mpf\web;

use mpf\base\AdvancedMethodCallTrait;
use mpf\base\LogAwareObject;
use mpf\interfaces\HtmlRequestInterface;

class Controller extends LogAwareObject {

    use AdvancedMethodCallTrait;

    /**
     * Connection to active request class;
     * @var \mpf\interfaces\HtmlRequestInterface
     */
    protected $request;

    /**
     * Name of the default controller action;
     * @var string
     */
    public $defaultAction = 'index';

    /**
     * Name of the current action;
     * @var string
     */
    public $currentAction;

    /**
     * Used by WebApp so that it won't send user $_GET options to class attributes;
     * @var boolean
     */
    public $safeClassOptions = false;

    /**
     * Path to views folder.
     * @var string
     */
    public $viewsFolder = '{MODULE_FOLDER}views/pages/{CONTROLLER}';

    /**
     * Path to current layout
     * @var string
     */
    public $layoutFolder = '{MODULE_FOLDER}views/layout';

    /**
     * If there is no need for layout then set this to false;
     * @var bool
     */
    public $showLayout = true;

    /**
     * Set if it should show or not layout when is an ajax request
     * @var bool
     */
    public $showLayoutOnAjax = false;

    /**
     * If set it will use this as current page layout, if it's not set then it
     * will get action name as layout.
     * @var string
     */
    public $pageLayout;

    private $_displayVars = array();

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public function assign($name, $value) {
        $this->_displayVars[$name] = $value;
        return $this;
    }

    /**
     * Display a php file page.
     * @param string $file
     * @param array $vars
     */
    public function display($file, $vars = []) {
        if ((DIRECTORY_SEPARATOR !== $file[0]) && (':' !== $file[1])) { // get path if not full path was sent
            $moduleFolder = $this->request->getModule() ? \mpf\WebApp::get()->modulesNamespace . DIRECTORY_SEPARATOR . $this->request->getModule() . DIRECTORY_SEPARATOR : '';
            $controllerFolder = $this->request->getController();
            $viewsFolder = str_replace(array('{APP_ROOT}', '{MODULE_FOLDER}', '{CONTROLLER}', '{LIBS_FOLDER}'), array(
                APP_ROOT, $moduleFolder, $controllerFolder, LIBS_FOLDER
            ), $this->viewsFolder);
            $file = $viewsFolder . $file . '.php';
        }
        foreach ($this->_displayVars as $k=>$v){
            $$k = $v;
        }
        foreach ($vars as $k=>$v){
            $$k = $v;
        }
        require $file;
    }

    /**
     * Apply request options to class also, not just method params;
     * @return boolean
     */
    protected function applyParamsToClass() {
        return false;
    }

    public function run($arguments = array()) {
        if ($this->getRequest()->isAjaxRequest()){
            $this->showLayout = $this->showLayoutOnAjax;
        }

        $action = 'action' . ucfirst($this->getActiveAction());
        if (!$this->beforeAction($this->getActiveAction())) {
            return;
        }
        $moduleFolder = $this->getRequest()->getModulePath();
        $controllerFolder = $this->request->getController();
        $result = $this->callMethod($action, $arguments ? $arguments : ($this->request ? $this->request->getParams() : array()));
        if (!$this->afterAction($this->getActiveAction(), $result)) {
            return;
        }
        if ($this->showLayout) {
            $layoutFolder = str_replace(array('{APP_ROOT}', '{MODULE_FOLDER}', '{LIBS_FOLDER}'), array(APP_ROOT, $moduleFolder, LIBS_FOLDER), $this->layoutFolder);
            $this->display($layoutFolder . DIRECTORY_SEPARATOR . 'header.php');
        }
        $viewsFolder = str_replace(array('{APP_ROOT}', '{MODULE_FOLDER}', '{CONTROLLER}', '{LIBS_FOLDER}'), array(
            APP_ROOT, $moduleFolder, $controllerFolder, LIBS_FOLDER
        ), $this->viewsFolder);
        $page = $this->pageLayout ? $this->pageLayout : strtolower($this->getActiveAction());
        if (file_exists($viewsFolder . DIRECTORY_SEPARATOR . $page . '.php')) {
            $this->display($viewsFolder . DIRECTORY_SEPARATOR . $page . '.php');
        }
        if ($this->showLayout) {
            $this->display($layoutFolder . DIRECTORY_SEPARATOR . 'footer.php');
        }
    }

    /**
     * Set a request object;
     * @param \mpf\interfaces\HtmlRequestInterface $request
     * @return $this
     */
    public function setRequest(HtmlRequestInterface $request) {
        $this->request = $request;
        return $this;
    }

    /**
     * Get controller request object;
     * @return \mpf\interfaces\HtmlRequestInterface
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Set/change current action;
     * @param string $name
     * @return $this
     */
    public function setActiveAction($name) {
        $this->currentAction = $name;
        return $this;
    }

    /**
     * Get active action name;
     * @return string
     */
    public function getActiveAction() {
        return $this->currentAction ? $this->currentAction : $this->defaultAction;
    }

    protected function beforeAction($actionName) {
        return true;
    }

    protected function afterAction($actionName, &$result) {
        return true;
    }

}
