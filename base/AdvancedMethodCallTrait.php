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

namespace mpf\base;

/**
 * This trait offers 2 methods that help to call a callable function using an associated array as parameter so that
 * the order won't matter.
 *
 * This is used mainly by {@class:\mpf\web\Controller} and {@class:\mpf\cli\Command} to easily call actions using the
 * parameters sent to the app when executed(`$_GET` for web applications and console arguments for terminal applications).
 *
 * Example:
 * [php]class Test{
 *   use AdvancedMethodCallTrait;
 *
 *   function toTest($a=0, $b = 'something', $c = 'nothing'){
 *     return "[a={$a}, b={$b} and c = {$c}]";
 *   }
 * }
 *
 *
 *
 * $test = new Test();
 * echo $test->callMethod('toTest', ['c' => 'everything']);
 * // will output: "[a=0, b=something and c = everything]"
 * [/php]
 *
 * It does require 2 other methods to be defined in the class that uses this trait:
 *
 *   - `critical()`  to log any errors that could occur. Any {@class:\mpf\base\LogAwareObject} child should be fine.
 *   - `applyParamsToClass()` - this can be used to apply some of the parameters to the class attributes also. Must be careful to
 * not not overwrite any important attributes. This is used by {@class:\mpf\cli\Command} to apply some of the arguments sent in terminal to
 * current command and allows some general options like `--debug` to be available for all commands and actions. It is not recommended
 * for websites where there is no control over what the user input will be, for that a dummy method should be created that won't apply any changes.
 *
 * For details about the default uses of this trait check : {@method:\mpf\cli\Command::run()} and {@method:\mpf\web\Controller::run()} descriptions.
 */
trait AdvancedMethodCallTrait {

    /**
     * Records if there was an error or not when trying to call the method.
     *
     * @var bool
     */
    private $error;
    
    /**
     * Read parameters for searched method;
     * 
     * @param string $name
     * @param string[] $options
     * @return string[] $details
     */
    public function getMethodParameters($name, $options) {
        $method = new \ReflectionMethod($this, $name);

        $parameters = $method->getParameters();

        $details = array();
        foreach ($parameters as $param) {
            if (isset($options[$param->getName()])) {
                $details[] = $options[$param->getName()];
                unset($options[$param->getName()]);
            } elseif ($param->isDefaultValueAvailable()) {
                $details[] = $param->getDefaultValue();
            } else {
                if (is_a($this, '\\mpf\\interfaces\\LogAwareObjectInterface')) {
                    $this->critical("Missing parameter " . $param->getName() . "!", array('options' => $options));
                }
                return null;
            }
        }

        if ($options && $this->applyParamsToClass())
            foreach ($options as $k => $n) { // apply extra options 
                $this->$k = $n;
            }

        return $details;
    }

    /**
     * Call selected method using selected options;
     * 
     * @param string $methodName
     * @param string[] $options
     * @return mixed
     */
    public function callMethod($methodName, $options) {
        $this->error = false;
        if (null === ($options = $this->getMethodParameters($methodName, $options))) {
            $this->error = true;
            return null;
        }

        return call_user_func_array(array($this, $methodName), $options);
    }

}
