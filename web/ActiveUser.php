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

use mpf\base\App;
use mpf\base\LogAwareObject;
use mpf\interfaces\ActiveUserInterface;
use mpf\WebApp;

abstract class ActiveUser extends LogAwareObject implements ActiveUserInterface {

    private static $_instance;

    /**
     * Get an instance of current class
     * @return static
     */
    public static function get() {
        if (!self::$_instance) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * Key used for session data
     * @var string
     */
    public $sessionKey = 'ActiveUser';

    /**
     * Key used for cookie data
     * @var string
     */
    public $cookieKey = 'ActiveUser';

    /**
     * List of rights for active user
     * @var string[]
     */
    private $_rights = array();

    /**
     * Active user data loaded from $_SESSION
     * @var string[string]
     */
    private $_userData = array();

    /**
     * Record if user is connected or not.
     * @var boolean
     */
    protected $connected = false;

    public function init($config) {
        parent::init($config);

        $this->refresh();
        if ($this->isGuest()) {
            $this->checkAutoLogin();
        }
        return true;
    }

    /**
     * Check if user is not connected.
     * @return bool
     */
    public function isGuest() {
        return !$this->connected;
    }

    /**
     * Check if user is connected.
     * @return bool
     */
    public function isConnected() {
        return $this->connected;
    }

    /**
     * Logout for current user. It wil clear session and cookie.
     */
    public function logout() {
        $this->_userData = array();
        $this->_rights = array();
        Session::get()->delete(App::get()->shortName . $this->sessionKey);
        Cookie::get()->delete(App::get()->shortName . $this->cookieKey);
    }

    /**
     * Set user state.
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setState($name, $value) {
        $this->_userData[$name] = $value;
        Session::get()->updateListItem(App::get()->shortName . $this->sessionKey, 'vars', $this->_userData, array('vars' => array(), 'rights' => array()));
        return $this;
    }

    /**
     * Load data from $_SESSION if exists
     */
    protected function refresh() {
        if (Session::get()->exists(App::get()->shortName . $this->sessionKey)) {
            $state = Session::get()->value(App::get()->shortName . $this->sessionKey);
            $this->_userData = $state['vars'];
            $this->_rights = $state['rights'];
            $this->connected = (count($this->_userData) > 0);
        }
    }

    /**
     * Check if user has selected right.
     * @param $right
     * @return bool
     */
    public function hasRight($right) {
        return in_array($right, $this->_rights);
    }

    /**
     * Set a new set of rights for active user.
     * @param $rights
     * @return $this
     */
    public function setRights($rights) {
        $this->_rights = $rights;
        Session::get()->updateListItem(App::get()->shortName . $this->sessionKey, 'rights', $this->_rights, array('vars' => array(), 'rights' => array()));
        return $this;
    }

    /**
     * Return list of rights for current user.
     * @return \string[]
     */
    public function getRights() {
        return $this->_rights;
    }

    /**
     * Return value of  data from _userData for current user.
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->_userData[$name];
    }

    /**
     * A shortcut to setState method.
     * @param string $name
     * @param $value
     */
    public function __set($name, $value) {
        $this->setState($name, $value);
    }

    /**
     * Checks login from cookie or any other  used by user;
     */
    abstract protected function checkAutoLogin();
}
