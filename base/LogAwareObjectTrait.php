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

trait LogAwareObjectTrait {

    /**
     * ClassNames and options for every Log object to be used;
     * @var string[]
     */
    public $loggers = array(
        'mpf\\loggers\\NullLogger'
    );

    /**
     * List of all instantiated log classes
     * @var \mpf\loggers\Logger[]
     */
    private $loggersInstances = array();

    /**
     * Init logs for current component;
     * @param array $config
     * @return null
     * @throws \mpf\components\InvalidComponentException
     */
    protected function init($config = array()) {
        foreach ($this->loggers as $class => $config) {
            if (!is_array($config)) {
                $class = $config;
                $config = array();
            }
            $instance = $class::get($config);
            if (!is_a($instance, 'mpf\\loggers\\Logger'))
                throw new \mpf\components\InvalidComponentException("Loggers must extend mpf\\base\\Logger class!");
            $this->loggersInstances[] = $instance;
        }
        return true;
    }

    /**
     * Add a new logging engine;
     * Usage example:
     * 
     * $this->addLogger(MyCustomLogger::get());
     * 
     * @param \mpf\base\mpf\loggers\Logger $logger An insantiated object for selected engine;
     */
    public function addLogger(\mpf\loggers\Logger $logger) {
        $this->loggersInstances[] = $logger;
    }

    /**
     * Log alert messages!
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->alert($message, $context);
        }
    }

    /**
     * Log critical messages!
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->critical($message, $context);
        }
    }

    /**
     * Log debug messages;
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->debug($message, $context);
        }
    }

    /**
     * Log emergency messages;
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->emergency($message, $context);
        }
    }

    /**
     * Log error messages;
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->error($message, $context);
        }
    }

    /**
     * Log info messages;
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->info($message, $context);
        }
    }

    /**
     * Log messages;
     * @param int $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->log($level, $message, $context);
        }
    }

    /**
     * Log notice messages;
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->notice($message, $context);
        }
    }

    /**
     * Log warning messages
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array()) {
        $context['fromClass'] = get_called_class();
        foreach ($this->loggersInstances as $logger) {
            $logger->warning($message, $context);
        }
    }

    /**
     * Return list of logs from all Logger objects;
     * Some logger engines may  not return any logs per instance, instead store
     * them all and have an extra method to return all;
     * @param int $max Max number of logs
     * @param int $level Max level;
     */
    public function collectLogs($max = 300, $level = null) {
        
    }

}