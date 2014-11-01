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

class AccessMap extends \mpf\base\LogAwareObject implements \mpf\interfaces\AccessMapInterface {

    public $finalMap = array();
    public $map = array();

    public function init($config = array()) {
        $this->parseMap();
        return parent::init($config);
    }

    /**
     * Parse raw map to create  a script readable one.
     */
    protected function parseMap() {
        $this->finalMap = array();
        foreach ($this->map as $controller => $actions) {
            $this->finalMap[$controller] = isset($this->finalMap[$controller]) ? $this->finalMap[$controller] : array();
            foreach ($actions as $action => $rights) {
                $this->parseMapRow($controller, $action, $rights);
            }
        }
    }

    /**
     * Parses a single config row
     * @param string $controller
     * @param string $action
     * @param string $rights
     */
    protected function parseMapRow($controller, $action, $rights) {
        $controller= explode(',', $controller); //allow for multiple controllers on the same line separated by ,
        $rights = explode(',', $rights);
        foreach ($rights as &$right) {
            $right = trim($right);
        }
        $actionsList = explode(',', $action);
        foreach ($actionsList as $name) {
            foreach ($controller as $c){
                $this->finalMap[trim($c)][trim($name)] = $rights;
            }
        }
    }

    /**
     * Check access for specified controller and action.
     * @param string $controller
     * @param string $action
     * @param string|null $module
     * @return boolean
     */
    public function canAccess($controller, $action, $module = null) {
        $rights = $this->getRightsFromMap($controller, $action, $module);
        if ('*' == $rights[0]) {
            return true;
        }
        if ('@' == $rights[0]) {
            return !\mpf\WebApp::get()->user()->isGuest();
        }
        $hasRights = false;
        foreach ($rights as $right) {
            $hasRights = $hasRights or \mpf\WebApp::get()->user()->hasRight($right);
        }
        return $hasRights;
    }

    /**
     * Get rights associated for selected controller and action.
     * @param string $controller
     * @param string $action
     * @param string|null $module
     * @return string
     */
    protected function getRightsFromMap($controller, $action, $module) {
        list ($rController, $allController) = $this->getRawAndModuleControllers($controller, $module);
        if (isset($this->finalMap[$rController][$action])) {
            return $this->finalMap[$rController][$action];
        } elseif (isset($this->finalMap[$allController][$action])) {
            return $this->finalMap[$allController][$action];
        } elseif (isset($this->finalMap[$rController]['*'])) {
            return $this->finalMap[$rController]['*'];
        } elseif (isset($this->finalMap[$allController]['*'])) {
            return $this->finalMap[$allController]['*'];
        } elseif (isset($this->finalMap['*'][$action])) {
            return $this->finalMap['*'][$action];
        } elseif (isset($this->finalMap['*']['*'])) {
            return $this->finalMap['*']['*'];
        } else {
            return array('*');
        }
    }

    /**
     * Get variants to search in config for controller
     * @param string $controller
     * @param string|null $module
     * @return string
     */
    private function getRawAndModuleControllers($controller, $module) {
        if ('/' == $module || '' == $module) {
            $rController = $controller;
            $allController = '*';
        } elseif (null === $module) {
            $rController = \mpf\WebApp::get()->request()->getModule() . '/' . $controller;
            $allController = \mpf\WebApp::get()->request()->getModule() . '/*';
        } else {
            $rController = $module . '/' . $controller;
            $allController = $module . '/*';
        }
        return array($rController, $allController);
    }

}
