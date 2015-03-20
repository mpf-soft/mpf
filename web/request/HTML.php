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

namespace mpf\web\request;

use mpf\base\Config;
use mpf\base\LogAwareObject;
use mpf\interfaces\HtmlRequestInterface;
use mpf\loggers\DevLogger;
use mpf\web\Session;
use mpf\WebApp;

class HTML extends LogAwareObject implements HtmlRequestInterface {

    protected $_deleteParams;

    protected $_putParams;
    /**
     * Records browser preferred language
     * @var string
     */
    protected $_preferredLanguage;

    /**
     * Records current URL used later to extract all info.
     * @var string
     */
    protected $currentURL;

    /**
     * Records current domain, port and protocol.
     * @var string
     */
    protected $currentDomain;

    /**
     * Base URL from where link root and web root is determined.
     * @var string
     */
    protected $baseURL;

    /**
     * Set this to true to generate debug messages from when it parses current URL.
     * So that you can see what URI was used, what route was selected and what params were found
     * @var bool
     */
    public $debug = false;

    /**
     * List of available modules. Config options for each module can be set here but be careful because
     * they won't apply for already initialized classes except \mpf\WebApp and \mpf\web\request\HTML classes.
     * Example :
     *  [
     *      'admin',
     *      'mobile' => [
     *             'mpf\\WebApp' => [
     *                  'title' => 'Some new Title'
     *              ],
     *             'path' => '/full/path/to/model'
     *      ]
     *  ]
     * Optionally a "path" can be set for each module. In case that the path is set then a new namespace called:
     *   \app\modules\moduleName  will be added to autoloader with basePath specified here.
     * @var string[]
     */
    public $modules = array();

    /**
     * Useful when setting a different index file for a different module and don't want the linkroot to have module name in it.
     * To use this update that second index file with this lines just before App run:
     * Config::get()->set('\\mpf\\web\\request\\HTML', array(
     *       'defaultModule' => 'moduleName'
     *  ));
     * It can also be used to have different modules for access from different browsers( like a mobile module from phones and tables )
     * so it will have the same url but it will access the mobile module.
     *
     * @var string|null
     */
    public $defaultModule;

    /**
     * Name of the controller to be used when none it's specified;
     * @var string
     */
    public $defaultController = 'home';

    /**
     * If it's set to be SEO then it will generate the url using the templates. If not, it will use
     * basig GET;
     * @var boolean
     */
    public $SEO = false;

    /**
     * If is secured then CSRF token will be generated and checked
     * @var boolean
     */
    public $secure = true;

    /**
     * Name of the CSRF token key.
     * @var string
     */
    protected $csrfKey = 'MPF_CSRF_TOKEN';

    /**
     * Used to generate hash for CSRF value
     * @var string
     */
    public $csrfSalt = 'D$F#$dx32x43';

    /**
     * Active controller
     * @var string
     */
    private $controller;

    /**
     * List of URL route templates. From here is specified how controller and action can be extracted but
     * also a different parameter if needed.
     * Structure:
     *  array( regexp => list of obtained params)
     * Example
     *  array('([a-z]{2})/([a-zA-Z0-9]+)' => 'language,controller')
     * Priority will be determined by the order they are entered here.
     * @var array
     */
    public $urlRoutes = array(
        '(?<language>[a-z]{2})\/(?<controller>[a-zA-Z0-9]+)\/(?<action>[a-zA-Z0-9_\-]+)\/(?<id>[0-9]+)', // language & controller & action  & id
        '(?<controller>[a-zA-Z0-9]+)\/(?<action>[a-zA-Z0-9_\-]+)\/(?<id>[0-9]+)', // controller & action & id
        '(?<language>[a-z]{2})\/(?<controller>[a-zA-Z0-9]+)\/(?<action>[a-zA-Z0-9_\-]+)', // language & controller & action
        '(?<language>[a-z]{2})\/(?<controller>[a-zA-Z0-9]+)\/(?<id>[0-9]+)' => array('action' => 'view'), // language & controller & view id
        '(?<controller>[a-zA-Z0-9]+)\/(?<id>[0-9]+)' => array('action' => 'view'), // controller & view id
        '(?<language>[a-z]{2})\/(?<controller>[a-zA-Z0-9]+)', // language & controller
        '(?<controller>[a-zA-Z0-9]+)\/(?<action>[a-zA-Z0-9_\-]+)', // controller & action
        '(?<controller>[a-zA-Z0-9]+)' // controller
    );

    /**
     * Must include keywords {name} and {value} in for that it will be used later by the app.
     * @var string
     */
    public $paramsPairStructure = '{name},{value}';

    /**
     * Separator used between pairs of name&value for parameters.
     * @var string
     */
    public $paramsSeparator = '/';

    /**
     * Active module
     * @var string
     */
    private $module;

    /**
     * Full path to current module
     * @var string
     */
    private $modulePath;

    /**
     * Active action
     * @var string
     */
    private $action;

    /**
     * Request parameters
     * @var string
     */
    private $params;

    /**
     * List of instantiated classes
     * @var HTML[]
     */
    private static $_instances = array();

    /**
     * Calculates current URL and returns it.
     * @return string
     */
    protected function calculateCurrentURL() {
        $url = "http";
        if ($this->isSecureConnection()) {

            $url .= 's://' . $_SERVER['SERVER_NAME'] . ('443' != $_SERVER['SERVER_PORT'] ? ':' . $_SERVER['SERVER_PORT'] : '');
        } else {
            $url .= '://' . $_SERVER['SERVER_NAME'] . ('80' != $_SERVER['SERVER_PORT'] ? ':' . $_SERVER['SERVER_PORT'] : '');
        }
        $this->currentDomain = $url . '/';
        $this->currentURL = $url . $_SERVER['REQUEST_URI'];
        if ($_SERVER['DOCUMENT_ROOT'][strlen($_SERVER['DOCUMENT_ROOT']) - 1] == '/') {
            $docRoot = substr($_SERVER['DOCUMENT_ROOT'], 0, strlen($_SERVER['DOCUMENT_ROOT']) - 1);
        } else {
            $docRoot = $_SERVER['DOCUMENT_ROOT'];
        }
        $secondPart = str_replace($docRoot, '', dirname($_SERVER['SCRIPT_FILENAME'])) . '/';
        $this->baseURL = $url . (('/' == $secondPart[0]) ? $secondPart : '/' . $secondPart);
    }

    /**
     * Updates Config class with values from Module config.
     * @param string [string] $config
     * @return $this
     */
    protected function applyModuleConfig($config) {
        if (isset($config['mpf\\web\\request\\HTML'])) {
            foreach ($config['mpf\\web\\request\\HTML'] as $k => $v) {
                $this->$k = $v;
            }
        }
        if (isset($config['mpf\\WebApp'])) {
            foreach ($config['mpf\\WebApp'] as $k => $v) {
                WebApp::get()->$k = $v;
            }
        }
        foreach ($config as $class => $options) {
            Config::get()->set($class, $options);
        }
        return $this;
    }

    /**
     * Calculates module path to be later used for viewers.
     */
    protected function calculateModulePath() {
        if (!$this->module || '/' == $this->module) {
            $this->modulePath = APP_ROOT;
        } else {
            if (isset($this->modules[$this->module]) && isset($this->modules[$this->module]['path'])) {
                $this->modulePath = $this->modules[$this->module]['path'];
            } else {
                $this->modulePath = APP_ROOT . 'modules' . DIRECTORY_SEPARATOR . $this->module . DIRECTORY_SEPARATOR;
            }
        }
    }

    /**
     * Calculates controller, action, model and all extra info.
     */
    protected function updateURLData() {
        $uri = substr($this->currentURL, strlen($this->baseURL));
        if (!trim($uri) || '/' == $uri) {
            $this->module = $this->defaultModule;
            $this->controller = $this->defaultController;
            $this->action = null;
            $this->calculateModulePath();
            return;
        }
        $module = explode('/', $uri, 2); // search for module
        foreach ($this->modules as $mod => $details) {
            if (is_array($details) && $module[0] == $mod) {
                $this->applyModuleConfig($details);
                $this->module = $module[0];
                $uri = $module[1];
                break;
            }
            if ($details == $module[0]) {
                $this->module = $module[0];
                $uri = $module[1];
                break;
            }
        }
        $this->calculateModulePath();
        if (!trim($uri)) {
            $this->controller = $this->defaultController;
            $this->action = null;
            return;
        }
        unset($module);
        if ($uri[0] == '/') {
            $uri = substr($uri, 1); // remove slash from start.
        }

        if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) { // remove query part from url.
            $uri = substr($uri, 0, strlen($uri) - strlen($_SERVER['QUERY_STRING']) - 1);
        }
        if ('.html' == substr($uri, -5)) {
            $uri = substr($uri, 0, strlen($uri) - 5); // remove .html from the end.
        }

        // search for the route.
        $this->params = $_GET;
        foreach ($this->urlRoutes as $route => $defined) {
            $expression = is_numeric($route) ? '/^' . $defined . '$/' : '/^' . $route . '$/';
            $result = preg_match($expression, $uri, $matches);
            if (!$result) {
                $expression = is_numeric($route) ? '/^' . $defined . '\/(?<params>.*)$/' : '/^' . $route . '\/(?<params>.*)$/';
                $result = preg_match($expression, $uri, $matches);
            }
            if (!$result) {
                continue; //not matched
            }
            if ($this->debug) {
                $this->debug($uri . ' -- ' . $expression);
            }
            $this->_updateURLDataUsingMatches($matches, is_numeric($route) ? "" : $defined);
            break;
        }
        $_GET = $this->params;
    }

    protected function _updateURLDataUsingMatches($matches, $defined) {
        if ($defined) {
            foreach ($defined as $k => $v) {
                $matches[$k] = $v;
            }
        }
        foreach ($matches as $name => $value) {
            if (is_numeric($name)) {
                continue; // skip  extra matches;
            }
            switch ($name) {
                case 'module':
                    $this->debug("Module matched: $value");
                    $this->module = $value;
                    if (isset($this->modules[$value])) {
                        $this->applyModuleConfig($this->modules[$value]);
                    }
                    break;
                case 'controller':
                    $this->debug("Controller matched: $value");
                    $this->controller = $value;
                    break;
                case 'action':
                    if (false !== strpos($value, '-')) {
                        $chars = str_split($value);
                        $mustUpdate = false;
                        foreach ($chars as $k => $char) {
                            if ('-' == $char) {
                                $chars[$k] = '';
                                $mustUpdate = true;
                                continue;
                            }
                            if ($mustUpdate) {
                                $chars[$k] = strtoupper($char);
                            }
                        }
                        $this->action = implode("", $chars);
                    } else {
                        $this->action = $value;
                    }
                    $this->debug("Action matched: {$this->action}");
                    break;
                case 'params':
                    $this->_addToParamsFromString($value);
                    break;
                default:
                    $this->debug("$name matched: $value");
                    $this->params[$name] = $value;
            }
        }
        if ($this->debug) {
            $this->debug('Params: ' . print_r($this->params, true));
        }
    }

    protected function _addToParamsFromString($paramString) {
        $paramPairs = explode($this->paramsSeparator, $paramString);
        $exp = str_replace(array('{name}', '{value}'), array('(?<name>[a-zA-Z0-9_\-\[\]]+)', '(?<value>.*)'), $this->paramsPairStructure);
        foreach ($paramPairs as $pair) {
            $pair = urldecode($pair);
            if (preg_match('/^' . $exp . '$/', $pair, $matches)) {
                $this->params = $this->addValue($this->params, $matches['name'], $matches['value']);
            }
        }
    }

    protected function addValue($original, $name, $value) {
        $name = explode('[', $name, 2);
        if ($name[0][strlen($name[0]) - 1] == ']') {
            $name[0] = substr($name[0], 0, strlen($name[0]) - 1);
        }
        if (count($name) == 2) {
            if (!isset($original[$name[0]])) {
                $original[$name[0]] = array();
            }
            $original[$name[0]] = $this->addValue($original[$name[0]], $name[1], $value);
        } else {
            $original[$name[0]] = $value;
        }
        return $original;
    }

    protected function init($options = array()) {
        parent::init($options);
        self::$_instances[md5(serialize($options))] = $this;
        $this->normalizeRequest();
        $this->calculateCurrentURL();
        if ($this->secure && isset($_POST) && count($_POST)) {
            $key = $this->getCsrfKey();
            $value = $this->getCsrfValue();
            if (!isset($_POST[$key])) {
                $this->error('CSRF token missing!');
                list($this->controller, $this->action) = WebApp::get()->pageAccessDenied;
                $this->calculateModulePath();
                return;
            } elseif ($_POST[$key] != $value) {
                $this->error('Invalid CSRF token!' . $_POST[$key] . ' != ' . $value);
                list($this->controller, $this->action) = WebApp::get()->pageAccessDenied;
                $this->calculateModulePath();
                return;
            }
            unset($_POST[$key]);
        }
        if (!$this->SEO) {
            $this->module = isset($_GET['module']) ? $_GET['module'] : $this->defaultModule;
            $this->controller = isset($_GET['controller']) ? $_GET['controller'] : $this->defaultController;
            $this->action = isset($_GET['action']) ? $_GET['action'] : null;
            $this->calculateModulePath();
        } else {
            $this->updateURLData();
        }
        foreach ($this->modules as $name => $details) {
            if (is_array($details) && isset($details['path'])) {
                WebApp::get()->autoload()->addPsr4('\\app\\modules\\' . $name, $details['path'], true);
            }
        }
    }

    /**
     * Changes path for current module. Is called by WebApp but in some exceptions it can be called by any other class.
     * @param string $path
     */
    public function setModulePath($path) {
        $this->modulePath = $path;
    }

    /**
     * Return an instantiated class of HTML
     * @param string[] $options
     * @return \mpf\web\request\HTML
     */
    public static function get($options = array()) {
        if (!isset(self::$_instances[md5(serialize($options))])) {
            return new HTML($options);
        }

        return self::$_instances[md5(serialize($options))];
    }

    /**
     * Get url to the selected page;
     * @param string $controller Name of the controller where the URL must link
     * @param string $action Name of the action where the URL must link
     * @param array $params List of associative parameters
     * @param string $module Name of the module where the URL must link. If you want to removed current module and go to default use false. If none it's set it will use current module;
     * @return string
     */
    public function createURL($controller, $action = null, $params = array(), $module = null) {
        if (null == $controller)
            $controller = $this->getController();
        if (!$this->SEO) {
            $params['controller'] = $controller;
            $mod = (null !== $module) ? $module : $this->getModule();
            if ($mod != $this->defaultModule) {
                $params['module'] = $mod;
            }
            if ($action)
                $params['action'] = $action;
            return '?' . http_build_query($params);
        }

        $url = $this->getLinkRoot();
        if ((!is_null($module)) && $module != $this->module) {
            if ($this->module != $this->defaultModule) {
                if ($module = $this->defaultModule) {
                    $url = str_replace('/' . $this->module . '/', '/', $url);
                } else {
                    $url = str_replace('/' . $this->module . '/', '/' . $module . '/', $url);
                    $params['module'] = $this->module;
                }
            } elseif ($module != $this->defaultModule) {
                $url .= $module . '/';
                $params['module'] = $module;
            }
        } elseif ($this->module) {
            $params['module'] = $this->module;
        }

        return $this->_createURL($url, $controller, $action, $this->_prepareParams($params));
    }

    protected function _prepareParams($params) {
        $result = array();
        foreach ($params as $name => $value) {
            if (is_array($value)) {
                $value = $this->_prepareParams($value);
                foreach ($value as $k => $v) {
                    $k = explode('[', $k, 2);
                    $result[$name . '[' . $k[0] . ']' . (isset($k[1]) ? '[' . $k[1] : '')] = $v;
                }
            } else {
                $result[$name] = $value;
            }

        }
        return $result;
    }

    /**
     * Create url by checking all routes.
     * @param string $base
     * @param string $controller
     * @param string|null $action
     * @param string [string] $params
     * @param boolean $searchDefined
     * @return string
     */
    protected function _createURL($base, $controller, $action, $params, $searchDefined = true) {
        $params['controller'] = $controller;
        $params['action'] = $action;
        foreach ($this->urlRoutes as $route => $defined) { // first check those with strict params.
            $cBase = $base;
            $preparedParams = $params;
            if ($searchDefined) {
                if (is_numeric($route)) {
                    continue; // first only get those with defined conditions.
                }
                $match = true;
                foreach ($defined as $def => $value) { // first check if those exact values match.
                    if (($value || '0' === $value) && (!isset($params[$def]) || $params[$def] != $value)) {
                        $match = false;
                    } else {
                        unset($preparedParams[$def]);
                        if ('module' == $def) {
                            if (substr($cBase, -1 * strlen($value . '/')) == $value . '/') { // remove module from base url if it's there
                                $cBase = substr($cBase, 0, strlen($cBase) - strlen($value.'/'));
                            }
                        }
                    }
                }
                if (!$match) {
                    continue;
                }
            }
            $match = true;
            preg_match_all('/\(\?<([a-z0-9_\-]+)>.*?\)/', is_numeric($route) ? $defined : $route, $matches);
            if (!$matches) {
                continue;
            }
            $toReplace = array('\/' => '/');
            foreach ($matches[1] as $k => $word) {
                $match = $match & isset($params[$word]);
                if ($match) {
                    $toReplace[$matches[0][$k]] = $params[$word];
                    unset($preparedParams[$word]);
                } else {
                    break;
                }
            }

            if ($match) {
                unset($preparedParams['module']);
                return $cBase . str_replace(array_keys($toReplace), array_values($toReplace), is_numeric($route) ? $defined : $route) . $this->_params2string($preparedParams) . '.html';
            }
        }
        if ($searchDefined) {
            return $this->_createURL($base, $controller, $action, $params, false);
        }

    }

    /**
     * Get query params from array.
     * @param $params
     * @return string
     */
    protected function _params2string($params) {
        if (!count($params)) {
            return '';
        }
        $paramsPart = array();
        foreach ($params as $name => $value) {
            if (!$value && ('0' !== $value)) {
                continue;
            }
            $paramsPart[] = str_replace(array('{name}', '{value}'), array($name, urlencode($value)), $this->paramsPairStructure);
        }
        if (!count($paramsPart)) {
            return '';
        }
        return '/' . implode($this->paramsSeparator, $paramsPart);
    }

    /**
     * Get current action name
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Change current action
     * @param string $name
     */
    public function setAction($name) {
        $this->action = $name;
    }

    /**
     * Get current controller name
     * @return string
     */
    public function getController() {
        return $this->controller ? $this->controller : $this->defaultController;
    }

    /**
     * Change current controller
     * @param string $name
     */
    public function setController($name) {
        $this->controller = $name;
    }

    /**
     * Get full current URL;
     * @return string
     */
    public function getCurrentURL() {
        return $this->currentURL;
    }

    /**
     * Get active module name;
     * @return string
     */
    public function getModule() {
        return $this->module;
    }

    /**
     * Get an associative list of parameters and values;
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Go back to the last page
     * @return null
     */
    public function goBack() {
        $this->goToURL($this->getReferrer());
    }

    /**
     * Redirect to generated internal URL;
     * @return null
     */
    public function goToPage($controller, $action = null, $params = array(), $module = null) {
        $this->goToURL($this->createURL($controller, $action, $params, $module));
    }

    /**
     * Redirect to selected string URL;
     * @return null
     */
    public function goToURL($url) {
        header('Location: ' . $url);
        die();
    }

    /**
     * Reload current page
     * @return null
     */
    public function reloadPage() {
        $this->goToURL($this->getCurrentURL());
    }

    /**
     * Will replace current HTML request with selected URI. This is used by HtmlRequest
     * unit test.
     * @return null
     */
    public function simulateURI($uri) {
        $this->currentURL = $this->baseURL . ('/' == $uri[0] ? substr($uri, 1) : $uri);
    }

    /**
     * Strips slashes from input data.
     * This method is applied when magic quotes is enabled.
     * @param string[] $data input data to be processed
     * @return string[] processed data
     */
    public function stripSlashes(&$data) {
        if (is_array($data)) {
            if (count($data) == 0)
                return $data;
            $keys = array_map('stripslashes', array_keys($data));
            $data = array_combine($keys, array_values($data));
            return array_map(array($this, 'stripSlashes'), $data);
        } else
            return stripslashes($data);
    }

    /**
     * Normalizes the request data.
     * This method strips off slashes in request data if get_magic_quotes_gpc() returns true.
     */
    protected function normalizeRequest() {
        // normalize request
        if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
            if (isset($_GET))
                $_GET = $this->stripSlashes($_GET);
            if (isset($_POST))
                $_POST = $this->stripSlashes($_POST);
            if (isset($_REQUEST))
                $_REQUEST = $this->stripSlashes($_REQUEST);
            if (isset($_COOKIE))
                $_COOKIE = $this->stripSlashes($_COOKIE);
        }
    }

    public function getLinkRoot() {
        return $this->baseURL . ($this->module != $this->defaultModule ? $this->module . '/' : '');
    }

    public function getWebRoot() {
        return $this->baseURL;
    }

    public function getPreferredLanguage() {
        if ($this->_preferredLanguage === null) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && ($n = preg_match_all('/([\w\-_]+)\s*(;\s*q\s*=\s*(\d*\.\d*))?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) > 0) {
                $languages = array();
                for ($i = 0; $i < $n; ++$i)
                    $languages[$matches[1][$i]] = empty($matches[3][$i]) ? 1.0 : floatval($matches[3][$i]);
                arsort($languages);
                foreach ($languages as $language => $pref)
                    return $this->_preferredLanguage = CLocale::getCanonicalID($language);
            }
            return $this->_preferredLanguage = false;
        }
        return $this->_preferredLanguage;
    }

    public function sendFile($fileName, $content, $mimeType = null, $terminate = true) {
        DevLogger::$ignoreOutput = true;
        if ($mimeType === null) {
            if (($mimeType = CFileHelper::getMimeTypeByExtension($fileName)) === null)
                $mimeType = 'text/plain';
        }
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header("Content-type: $mimeType");
        if (ob_get_length() === false)
            header('Content-Length: ' . (function_exists('mb_strlen') ? mb_strlen($content, '8bit') : strlen($content)));
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Content-Transfer-Encoding: binary');

        if ($terminate) {
            // clean up the application first because the file downloading could take long time
            // which may cause timeout of some resources (such as DB connection)
            echo $content;
            exit(0);
        } else
            echo $content;
    }

    /**
     * Sends existing file to a browser as a download using x-sendfile.
     *
     * X-Sendfile is a feature allowing a web application to redirect the request for a file to the webserver
     * that in turn processes the request, this way eliminating the need to perform tasks like reading the file
     * and sending it to the user. When dealing with a lot of files (or very big files) this can lead to a great
     * increase in performance as the web application is allowed to terminate earlier while the webserver is
     * handling the request.
     *
     * The request is sent to the server through a special non-standard HTTP-header.
     * When the web server encounters the presence of such header it will discard all output and send the file
     * specified by that header using web server internals including all optimizations like caching-headers.
     *
     * As this header directive is non-standard different directives exists for different web servers applications:
     * <ul>
     * <li>Apache: {@link http://tn123.org/mod_xsendfile X-Sendfile}</li>
     * <li>Lighttpd v1.4: {@link http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file X-LIGHTTPD-send-file}</li>
     * <li>Lighttpd v1.5: {@link http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file X-Sendfile}</li>
     * <li>Nginx: {@link http://wiki.nginx.org/XSendfile X-Accel-Redirect}</li>
     * <li>Cherokee: {@link http://www.cherokee-project.com/doc/other_goodies.html#x-sendfile X-Sendfile and X-Accel-Redirect}</li>
     * </ul>
     * So for this method to work the X-SENDFILE option/module should be enabled by the web server and
     * a proper xHeader should be sent.
     *
     * <b>Note:</b>
     * This option allows to download files that are not under web folders, and even files that are otherwise protected (deny from all) like .htaccess
     *
     * <b>Side effects</b>:
     * If this option is disabled by the web server, when this method is called a download configuration dialog
     * will open but the downloaded file will have 0 bytes.
     *
     * <b>Example</b>:
     * <pre>
     * <?php
     *    Yii::app()->request->xSendFile('/home/user/Pictures/picture1.jpg',array(
     *        'saveName'=>'image1.jpg',
     *        'mimeType'=>'image/jpeg',
     *        'terminate'=>false,
     *    ));
     * ?>
     * </pre>
     * @param string $filePath file name with full path
     * @param array $options additional options:
     * <ul>
     * <li>saveName: file name shown to the user, if not set real file name will be used</li>
     * <li>mimeType: mime type of the file, if not set it will be guessed automatically based on the file name, if set to null no content-type header will be sent.</li>
     * <li>xHeader: appropriate x-sendfile header, defaults to "X-Sendfile"</li>
     * <li>terminate: whether to terminate the current application after calling this method, defaults to true</li>
     * <li>forceDownload: specifies whether the file will be downloaded or shown inline, defaults to true. (Since version 1.1.9.)</li>
     * <li>addHeaders: an array of additional http headers in header-value pairs (available since version 1.1.10)</li>
     * </ul>
     * @param string $filePath
     * @param array $options
     * @return null
     */
    public function xSendFile($filePath, $options = array()) {
        DevLogger::$ignoreOutput = true;
        if (!isset($options['forceDownload']) || $options['forceDownload'])
            $disposition = 'attachment';
        else
            $disposition = 'inline';

        if (!isset($options['saveName']))
            $options['saveName'] = basename($filePath);

        if (!isset($options['mimeType'])) {
            if (($options['mimeType'] = CFileHelper::getMimeTypeByExtension($filePath)) === null)
                $options['mimeType'] = 'text/plain';
        }

        if (!isset($options['xHeader']))
            $options['xHeader'] = 'X-Sendfile';

        if ($options['mimeType'] !== null)
            header('Content-type: ' . $options['mimeType']);
        header('Content-Disposition: ' . $disposition . '; filename="' . $options['saveName'] . '"');
        if (isset($options['addHeaders'])) {
            foreach ($options['addHeaders'] as $header => $value)
                header($header . ': ' . $value);
        }
        header(trim($options['xHeader']) . ': ' . $filePath);

        if (!isset($options['terminate']) || $options['terminate']) {
            die();
        }
    }

    /**
     * @return bool
     */
    public function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * @return bool
     */
    public function isDeleteRequest() {
        return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'DELETE');
    }

    /**
     * @return bool
     */
    public function isPutRequest() {
        return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'PUT');
    }

    /**
     * @return bool
     */
    public function isPostRequest() {
        return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'], 'POST');
    }

    /**
     * @return bool
     */
    public function isSecureConnection() {
        return isset($_SERVER['HTTPS']) && !strcasecmp($_SERVER['HTTPS'], 'on');
    }

    /**
     * Get URL referrer. Similar to goBack(), but instead of redirect will return the address;
     * @return string
     */

    public function getReferrer() {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }

    public function getUserAgent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    protected function getRestParams() {
        $result = array();
        if (function_exists('mb_parse_str'))
            mb_parse_str(file_get_contents('php://input'), $result);
        else
            parse_str(file_get_contents('php://input'), $result);
        return $result;
    }

    public function getDelete($name, $defaultValue = null) {
        if ($this->_deleteParams === null)
            $this->_deleteParams = $this->isDeleteRequest() ? $this->getRestParams() : [];
        return isset($this->_deleteParams[$name]) ? $this->_deleteParams[$name] : $defaultValue;
    }

    public function getPut($name, $defaultValue = null) {
        if ($this->_putParams === null)
            $this->_putParams = $this->isPutRequest() ? $this->getRestParams() : [];
        return isset($this->_putParams[$name]) ? $this->_putParams[$name] : $defaultValue;
    }

    /**
     * Get csrf token key
     * @return string
     */
    public function getCsrfKey() {
        return $this->csrfKey;
    }

    /**
     * Get csrf token value
     * @return string
     */
    public function getCsrfValue() {
        return md5($this->getUserAgent() . Session::get()->id() . $this->csrfSalt);
    }

    /**
     * Get full path for current module
     * @return string
     */
    public function getModulePath() {
        return $this->modulePath;
    }
}