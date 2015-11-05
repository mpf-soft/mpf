<?php

/*
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

namespace mpf\loggers;
use mpf\base\Object;

/**
 * @author Mirel Nicu Mitache <mirel.mitache@gmail.com>
 * @package MPF Framework
 * @link    http://www.mpfframework.com
 * @category core package
 * @version 1.0
 * @since MPF Framework Version 1.0
 * @copyright Copyright &copy; 2011 Mirel Mitache 
 * @license  http://www.mpfframework.com/licence
 */
abstract class Logger extends Object {

    use LoggerTrait;

    private static $instance;

    protected function init($config = []) {
        self::$instance[get_class($this)] = $this;
        parent::init($config);
    }

    /**
     * Get active Logger instance;
     * 
     * @param string[] $config
     * @return \mpf\loggers\Logger
     */
    public static function get($config = []) {
        $class = get_called_class();
        if (self::$instance[$class])
            return self::$instance[$class];
        return new $class($config);
    }

    public $visibleLevels = [
        Levels::EMERGENCY,
        Levels::CRITICAL,
        Levels::ALERT,
        Levels::ERROR,
        Levels::WARNING,
        Levels::NOTICE,
        Levels::INFO,
        Levels::DEBUG
    ];
    public $detaliedLevels = [
        Levels::EMERGENCY,
        Levels::CRITICAL,
        Levels::ALERT,
        Levels::ERROR,
        Levels::WARNING,
        Levels::NOTICE
    ];

    /**
     * List of ignored classes. This only works for DEBUG messages.
     * @var array
     */
    public $ignoredClasses = [];

    abstract function getLogs();
}
