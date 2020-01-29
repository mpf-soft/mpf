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

namespace mpf\cli;

use mpf\base\AdvancedMethodCallTrait;
use mpf\base\LogAwareObject;
use mpf\loggers\InlineCliLogger;
use mpf\datasources\sql\PDOConnection;

class Command extends LogAwareObject
{

    use AdvancedMethodCallTrait;

    public $debug = false;
    public $defaultAction = 'index';

    /**
     * To be set by setActiveAction() and read by getActiveAction()
     * @var string
     */
    private $currentAction;

    /**
     * Apply request options to class also, not just method params;
     * @return boolean
     */
    protected function applyParamsToClass(): bool
    {
        return true;
    }

    /**
     * Set/change current action;
     * @param string $name
     * @return $this
     */
    public function setActiveAction($name): self
    {
        $this->currentAction = $name;
        return $this;
    }

    /**
     * Get active action name;
     * @return string
     */
    public function getActiveAction(): string
    {
        return $this->currentAction ? $this->currentAction : $this->defaultAction;
    }

    /**
     * You can overwrite this method to add portion of the code to be executed every time before action.
     * @param string $actionName name of the action to be executed, except for action part
     * @return boolean true to continue
     */
    protected function beforeAction($actionName): bool
    {
        if (!$this->debug) {
            InlineCliLogger::get()->ignoredClasses = array(PDOConnection::class);
            Helper::get()->showProgressBar = false;
        }
        return true;
    }

    /**
     *
     * @param string $actionName
     * @param mixed $result
     * @return boolean true to continue
     */
    protected function afterAction($actionName, $result): bool
    {
        return true;
    }

    /**
     * @param array $arguments
     * @throws \ReflectionException
     */
    public function run($arguments = array())
    {
        $action = 'action' . ucfirst($this->getActiveAction());
        try {
            if (!$this->canCallMethod($action, $arguments, true)) {
                return;
            }
        } catch (\ReflectionException $e){
            return;
        }
        if (!$this->beforeAction($this->getActiveAction())) {
            return;
        }
        $result = $this->callMethod($action, $arguments);
        if (!$this->afterAction($this->getActiveAction(), $result)) {
            return;
        }
    }

}
