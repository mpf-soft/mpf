<?php

namespace mpf;

class WebApp extends base\App {

    /**
     * List of aliases for selected controllers.
     * For example:
     *      'dev' => '\mpf\web\dev\Controller'
     * @var array
     */
    public $controllerAliases = array();

    /**
     * Name of the class used to handle HTML requests;
     * @var string
     */
    public $requestClass = '\\mpf\\web\\request\\HTML';

    /**
     * Class name for ActiveUser object;
     * @var string
     */
    public $activeUserClass = '\\app\\components\\ActiveUser';

    /**
     * Name of the controllers namespace. By default it's "controllers";
     * Same name must be used by the folders from both app folder and module
     * folder;
     * @var string
     */
    public $controllersNamespace = 'controllers';

    /**
     * Name of the modules namespace. Same name must be used by folder that
     * contains all modules;
     * @var string
     */
    public $modulesNamespace = 'modules';

    /**
     * Here can be defined namespaces for modules from other sources.
     * If folder it's outside the "libs" folder then an alias must be set in
     * Autoload for vendor location;
     * @var string[]
     */
    public $modulesAliases = array();

    /**
     * List of Controller & Action replacement page in case that the requeste page was not found.
     * @var string[]
     */
    public $pageNotFound = array('special', 'notFound');

    /**
     * List of Controller & Action where user is redirected if it tries to access
     * a page without the required rights;
     * @var string[]
     */
    public $pageAccessDenied = array('special', 'accessDenied');

    /**
     * List of Controller & Action where user is redirected when it tries to access a restricted page
     * but it's not logged in
     * @var string[]
     */
    public $pageLogin = array('user', 'login');

    /**
     * Object to be used as access map
     * @var \mpf\web\AccessMap
     */
    public $accessMap;

    /**
     * Link to active controller
     * @var \mpf\web\Controller
     */
    private $_controller;

    /**
     * This method will load the controller based on the Request and execute it;
     */
    protected function start() {
        $controllerClass = $this->calculateControllerClassFromRights();
        $controller = $this->loadController($controllerClass); // try to instantiate the controller
        if (!$controller) {
            $this->alert('Invalid controller ' . $controllerClass . '!');
            return; // stop execution if it's  an invalid controller
        }
        $controller->setActiveAction($this->request()->getAction());
        if (!method_exists($controller, 'action' . ucfirst($controller->getActiveAction()))) { //check if action exists;
            $this->alert('Action ' . $this->request()->getAction() . ' not found!', array(
                'requestedController' => $this->request()->getController(),
                'requestedModule' => $this->request()->getModule()
            ));
            $controller = $this->loadController($this->getPageNotFound());
            if (!$controller) {
                $this->alert('Invalid controller ' . $controllerClass . '!');
                return; // stop execution if it's  an invalid controller
            }
        }

        $this->_controller = $controller;

        $controller->setActiveAction($this->request()->getAction())
            ->setRequest($this->request())
            ->run();
    }

    /**
     * Get controller class by checking if user has rights to access current controller or not.
     * @return string
     */
    protected function calculateControllerClassFromRights() {
        $controllerClass = $this->getControllerClassFromNameAndModule($this->request()->getController(), $this->request()->getModule());
        if (!class_exists($controllerClass)) {
            $this->alert('Controller ' . $controllerClass . ' not found!', array(
                'requestedController' => $this->request()->getController(),
                'requestedModule' => $this->request()->getModule()
            ));
            $controllerClass = $this->getPageNotFound();
        } elseif ($this->accessMap && (!$this->accessMap->canAccess($this->request()->getController(), $this->request()->getAction(), $this->request()->getModule()))) {
            if ($this->user()->isGuest()) { // it's not loggedin , redirect to login page
                $controllerClass = $this->getPageLogin();
            } else { // it's loggedin but doesn't have the rights, redirect to access denied page
                $controllerClass = $this->getPageAccessDenied();
            }
        }
        return $controllerClass;
    }

    /**
     * Instantiate controller and check if class is correct;
     * @param string $class
     * @return \mpf\web\Controller
     */
    private function loadController($class) {
        $controller = new $class();
        if (!is_a($controller, '\\mpf\\web\\Controller')) {
            $this->critical('Controller `' . $class . '` must extend \\mpf\\web\\Controller!', array(
                'requestedController' => $this->request()->getController(),
                'requestedModule' => $this->request()->getModule()
            ));
            return null;
        }
        return $controller;
    }

    /**
     * Return an instance of active controller
     * @return \mpf\web\Controller
     */
    public function getController() {
        return $this->_controller;
    }

    /**
     * Returns full namespace and classname for selected controller.
     * Controller name is modified with ucfirst() method. Also 'app' it's
     * added as a vendor name in namespace and $this->controllersNamespace
     * as  subnamespace. In case of modules, if there are no aliases for
     * selected module then modulesNamespace it's added and then module name  plus
     * controllersNamespace, in case an alias it's found, then that alias it's
     * used instead of 'app', modulesNamespace and module name .
     *
     * Examples:
     *   Controller: home
     *   Module : -
     *   Result : \app\controllers\Home
     *
     *   Controller: home
     *   Module: admin
     *   Result: \app\modules\admin\controllers\Home
     *
     *   Controller: home
     *   Module: chat
     *   Alias for chat: outsidevendor\chatModule
     *   Result: \outsidevender\chatModule\controllers\Home
     *
     *
     * @param string $controller name of the controller
     * @param string $module name of the module
     * @return string
     */
    public function getControllerClassFromNameAndModule($controller, $module) {
        if (isset($this->controllerAliases[$module ? $module . '/' . $controller : $controller])) { // check for controller aliases first
            return $this->controllerAliases[$module ? $module . '/' . $controller : $controller];
        }
        if (!$module) {
            return '\\app\\' . $this->controllersNamespace . '\\' . ucfirst($controller);
        } elseif (isset($this->modulesAliases[$module])) {
            return '\\' . $this->modulesAliases[$module] . '\\' . $this->controllersNamespace . '\\' . ucfirst($controller);
        }
        return '\\app\\' . $this->modulesNamespace . '\\' . $module . '\\' . $this->controllersNamespace . '\\' . ucfirst($controller);
    }

    /**
     * Return controller class for in case that page was not found!
     * @return string
     */
    protected function getPageNotFound() {
        $this->debug('Controller and action changed to: ' . implode("/", $this->pageNotFound));
        $this->request()->setController($this->pageNotFound[0]);
        $this->request()->setAction($this->pageNotFound[1]);
        return $this->getControllerClassFromNameAndModule($this->pageNotFound[0], $this->request()->getModule());
    }

    /**
     * Return class name to access denied controller
     * @return string
     */
    protected function getPageAccessDenied() {
        $this->debug('Controller and action changed to: ' . implode("/", $this->pageAccessDenied));
        $this->request()->setController($this->pageAccessDenied[0]);
        $this->request()->setAction($this->pageAccessDenied[1]);
        return $this->getControllerClassFromNameAndModule($this->pageAccessDenied[0], $this->request()->getModule());
    }

    /**
     * Return class name to login controller
     * @return string
     */
    protected function getPageLogin() {
        $this->debug('Controller and action changed to: ' . implode("/", $this->pageLogin));
        $this->request()->setController($this->pageLogin[0]);
        $this->request()->setAction($this->pageLogin[1]);
        return $this->getControllerClassFromNameAndModule($this->pageLogin[0], $this->request()->getModule());
    }

    /**
     * @return \mpf\interfaces\HtmlRequestInterface
     */
    public function request() {
        $class = $this->requestClass;
        return $class::get();
    }

    /**
     * Get activeUser class;
     * @return \app\components\ActiveUser
     */
    public function user() {
        $class = $this->activeUserClass;
        return $class::get();
    }

    /**
     * Set a new object to be used as access map. Object must implement \mpf\interfaces\AccessMapInterface
     * @param \mpf\interfaces\AccessMapInterface $mapObject
     */
    public function useAccessMap(\mpf\interfaces\AccessMapInterface $mapObject) {
        $this->accessMap = $mapObject;
        return $this;
    }

    /**
     * Checks access to a specific controller and action.
     * @param string $controller name of the controller to be checked
     * @param string $action name of the action to be checked
     * @param string $module name of the module to be checked. If it's base website use '/' as value. If null is sent then it will use active module
     * @return boolean
     */
    public function hasAccessTo($controller, $action, $module = null) {
        if (!$this->accessMap) {
            return true; // if no access map is defined then it has access everywhere
        }
        return $this->accessMap->canAccess($controller, $action, $module);
    }

}
