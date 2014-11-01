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

trait AdvancedMethodCallTrait {

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
        $error = false;
        if (null === ($options = $this->getMethodParameters($methodName, $options))) {
            $error = true;
            return null;
        }

        return call_user_func_array(array($this, $methodName), $options);
    }

}
