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

namespace mpf\interfaces;

interface LogAwareObjectInterface {
    /**
     * Add a new logging engine;
     * Usage example:
     * 
     * $this->addLogger(MyCustomLogger::get());
     * 
     * @param \mpf\loggers\Logger $logger An insantiated object for selected engine;
     */
    public function addLogger(\mpf\loggers\Logger $logger);

    /**
     * Log alert messages!
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array());

    /**
     * Log critical messages!
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array());

    /**
     * Log debug messages;
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array());

    /**
     * Log emergency messages;
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array());

    /**
     * Log error messages;
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array());

    /**
     * Log info messages;
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array());

    /**
     * Log messages;
     * @param int $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array());

    /**
     * Log notice messages;
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array());

    /**
     * Log warning messages
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array());

    /**
     * Return list of logs from all Logger objects;
     * Some logger engines may  not return any logs per instance, instead store
     * them all and have an extra method to return all;
     * @param int $max Max number of logs
     * @param int $level Max level;
     */
    public function collectLogs($max = 300, $level = null);
}
