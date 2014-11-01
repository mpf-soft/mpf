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

namespace mpf\loggers;

use \mpf\cli\Helper as HCli;
use \Psr\Log\LogLevel as Levels;

class InlineCliLogger extends Logger {

    public $levelsToColors = array(
        Levels::EMERGENCY => HCli::CWHITE,
        Levels::ALERT => HCli::CLIGHT_RED,
        Levels::CRITICAL => HCli::CLIGHT_RED,
        Levels::ERROR => HCLi::CRED,
        Levels::WARNING => HCli::CPURPLE,
        Levels::NOTICE => HCli::CLIGHT_BLUE,
        Levels::INFO => HCli::CLIGHT_GREEN,
        Levels::DEBUG => HCli::CWHITE
    );
    public $levelsToBackground = array(
        Levels::EMERGENCY => HCli::BRED,
        Levels::CRITICAL => HCLi::BGRAY,
        Levels::ALERT => null,
        Levels::ERROR => null,
        Levels::WARNING => null,
        Levels::NOTICE => null,
        Levels::INFO => null,
        Levels::DEBUG => null
    );

    /**
     * Color of details;
     * @var string
     */
    public $detailsColor = HCli::CLIGHT_GRAY;

    /**
     * Color of time;
     * @var string
     */
    public $timeColor = HCli::CDARK_GRAY;

    /**
     * Records the number of logs for every type;
     * @var string[]
     */
    protected $numbers = array();

    public function getLogs() {
        return array();
    }

    public function log($level, $message, array $context = array()) {
        if (!in_array($level, $this->visibleLevels))
            return;
        if (Levels::DEBUG == $level && isset($context['fromClass']) && in_array($context['fromClass'], $this->ignoredClasses)) {
            return;
        }
        $this->numbers[$level] = isset($this->numbers[$level]) ? $this->numbers[$level] + 1 : 1;
        $r = $this->writeTime(isset($context['fromClass']) ? $context['fromClass'] : '') . " " . $this->colorizeLog($message, $level) . "\n";
        if (!in_array($level, $this->detaliedLevels)) {
            echo $r;
            return;
        }

        $details = '';
        foreach ($context as $k => $v) {
            if ('fromClass' == $k) {
                continue;
            }
            if (is_string($v) || is_numeric($v))
                $details .= " $k => " . $v . "\n";
            elseif (is_bool($v))
                $details .= " $k => " . ($v ? 'true' : 'false') . "\n";
            elseif (is_a($v, '\Exception')) {
                /* @var $v \Exception */
                $details .= " Location: " . $v->getFile() . ' [' . $v->getLine() . ']:';
                $details .= "\n" . $v->getTraceAsString();
            } else {
                $details .= " $k => " . print_r($v, true);
            }

        }
        echo $r . HCli::get()->color($details, $this->detailsColor);
    }

    protected function writeTime($className) {
        return HCli::get()->color(date('Y-m-d H:i:s') . ' ' . $className, $this->timeColor);
    }

    protected function colorizeLog($message, $level) {
        return HCli::get()->color($message, $this->levelsToColors[$level], $this->levelsToBackground[$level]);
    }

}
