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

class Helper extends \mpf\base\Object {

    private static $_instance;

    /**
     * Get instance of class.
     * @return static
     */
    public static function get() {
        if (!self::$_instance) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    // CONSOLE TEXT COLORS:
    const CBLACK = '0;30';
    const CDARK_GRAY = '1;30';
    const CBLUE = '0;34';
    const CLIGHT_BLUE = '1;34';
    const CGREEN = '0;32';
    const CLIGHT_GREEN = '1;32';
    const CCYAN = '0;36';
    const CLIGHT_CYAN = '1;36';
    const CRED = '0;31';
    const CLIGHT_RED = '1;31';
    const CPURPLE = '0;35';
    const CLIGHT_PURPLE = '1;35';
    const CBROWN = '0;33';
    const CYELLOW = '1;33';
    const CLIGHT_GRAY = '0;37';
    const CWHITE = '1;37';
    // CONSOLE BACKGROUND COLLORS:
    const BBLACK = '40';
    const BRED = '41';
    const BGREEN = '42';
    const BYELLOW = '43';
    const BBLUE = '44';
    const BMAGENTA = '45';
    const BCYAN = '46';
    const BGRAY = '47';

    /**
     * Record if it should display actions or not;
     * @var boolean
     */
    public $showActions = true;

    /**
     * Record if it should display progressBar or not;
     * @var boolean
     */
    public $showProgressBar = true;

    public function color($message, $color = self::CWHITE, $background = null) {
        if (!($color || $background)) {
            return $message;
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return $message;
        }
        return ($color ? "\033[" . $color . "m" : '') . ($background ? "\033[" . $background . "m" : '') . $message . "\033[0m";
    }

    /**
     * Start a new action
     * @param string $message
     * @param string $color one of class constants
     * @param int $microtime a custom start time;
     * @return string
     */
    public function startAction($message, $color = self::CWHITE, $microtime = null) {
        self::endAction();
        self::endProgressBar();
        self::$currentActionHadLogs = false;
        self::$pendingActionLogs = array();
        self::$currentAction = array('length' => 19 + strlen(self::$timeTextSeparator) + self::getTextLength($message),
            'errors' => false,
            'color' => $color,
            'message' => '',
            'time' => $microtime ? $microtime : microtime(true));
        $r = date('Y-m-d H:i:s') . self::$timeTextSeparator . self::color($message, $color);
        return $r;
    }

    /**
     * Continue an action.
     * @param string $message
     * @param string $color one of class constants
     * @return type
     */
    public function continueAction($message, $color = 'default') {
        if (!self::$currentAction)
            return;
        $color = ('default' == $color) ? self::$currentAction['color'] : $color;
        $r = (' ' . self::timeItTook(self::$currentAction['time']) . ' ' . self::color($message, $color));
        self::$currentAction['length'] += 2 + self::$lastTimeLenght + self::getTextLength($message);
        return $r;
    }

    /**
     * End a started action.
     * @param string $message
     * @param string $color
     * @return string
     */
    public function endAction($message = 'done', $color = self::CLIGHT_GREEN) {
        $r = '';
        if (empty(self::$currentAction)) {
            return $r;
        }
        $r .= self::$currentAction['message'];
        $time = self::timeItTook(self::$currentAction['time'], false);
        $length = (self::$currentActionHadLogs ? 0 : self::$currentAction['length']) + self::$lastTimeLenght + self::getTextLength(' [' . $message . '] ');
        $spaceLength = self::getScreen('columns') - $length - 2;
        $r .= ' ';
        if ($spaceLength > 0) {
            $r .= AviraLogger::color(str_repeat('.', $spaceLength), AviraLogger::CDARK_GRAY);
        }
        $r .= self::color(' [' . $message . '] ', $color) . self::color($time, self::$dateColor) . "\n";
        self::$currentAction = null;
        if (self::$pendingActionLogs) {
            $r .= implode("\n", self::$pendingActionLogs) . "\n";
        }
        return $r;
    }

    protected $requestsRepeats = 0;

    /**
     * Get current console dimensions. If is not run from console(and is a cron job or something) then some default values are returned
     * @param type $what
     * @return type
     */
    protected function getScreen($what) {
        self::$requestsRepeats++;
        if (!empty(self::$screen)) {
            if (self::$requestsRepeats < 10) {
                return self::$screen[$what];
            }
            self::$requestsRepeats = 0;
        }

        self::$screen['rows'] = self::$screen['columns'] = 0;
        preg_match_all("/rows.([0-9]+);.columns.([0-9]+);/", strtolower(exec('stty -a  2> /dev/null | grep columns')), $output);
        if (sizeof($output) == 3) {
            if (count($output[0])) {
                self::$screen['rows'] = $output[1][0];
                self::$screen['columns'] = $output[2][0];
            } else {
                self::$screen['columns'] = 140;
                self::$screen['rows'] = 40;
            }
        }
        return self::$screen[$what];
    }

    /**
     *
     * Returns a string with time difference between the start time and current time
     * @param float $startTime micro start time
     * @return string  time difference
     */
    public function timeItTook($startTime) {
        $time = microtime(true) - $startTime;
        $time = number_format($time, 4);
        $time = '[' . $time . 's]';
        self::$lastTimeLenght = strlen($time);
        if ($time < 60) {
            return self::color($time, self::$dateColor);
        }
        $minutes = (int)($time / 60);
        $seconds = $time - ($minutes * 60);
        $time = '[' . $minutes . 'm' . $seconds . 's]';
        self::$lastTimeLenght = strlen($time);
        return self::color($time, self::$dateColor);
    }

    private $activeProgressBar;

    /**
     * Start a progress bar;
     * @param int $total
     * @param int $done
     * @param int $width
     * @return boolean
     */
    public function progressBar($total = null, $done = null, $width = null) {
        if (empty($total) || ($total <= 0)) {
            return false;
        }

        //calculate width
        $columns = $this->getScreen('columns');
        if ($columns < 50)
            return false;
        if (null === $width) {
            $width = 200;
        }
        $extraWidth = strlen((string)$total) * 2 + 1;
        if ($width + 35 + $extraWidth > $columns) {
            $width = $columns - 35 - $extraWidth;
        }

        $this->$activeProgressBar = array(
            'width' => $width,
            'done' => $done,
            'total' => $total,
            'startTime' => microtime(true),
            'startProgress' => $done,
        );
        $this->progress(null, null, null, 0);
    }

    public function progress($message = null, $messageDebugLevel = 'debug', $progressValue = 1, $color = null) {
        if (null !== $message) {
            if (null === $messageDebugLevel) {
                $messageDebugLevel = ($wasObject === 'bad') ? self::DEBUG_ERRORS : self::DEBUG_DETAILS;
            }
            if (null === $color) {
                $color = ($wasObject === 'bad') ? self::CLIGHT_RED : null;
            }
            $message = $message . str_pad('', $this->getScreen('columns') - 19 - strlen(self::$timeTextSeparator) - $this->getTextLength($message));
            $this->log($message, $messageDebugLevel, $color, $wasObject, 'from-progress');
        }

        if (empty($this->$activeProgressBar)) {
            return;
        }
        $this->$activeProgressBar['done'] += $progressValue;
        // few display settings
        $colorDone = self::CLIGHT_GREEN;
        $colorTotal = self::CWHITE;
        $colorCurrent = self::CYELLOW;
        $columns = $this->getScreen('columns');
        // end settings
        $percent = round(($this->$activeProgressBar['done'] / $this->$activeProgressBar['total']) * 100);
        $fill = round($percent / (100 / $this->$activeProgressBar['width']));
        echo ' ';
        echo $this->color('[', $colorTotal);
        if ($fill && $fill < $this->$activeProgressBar['width']) {
            if (($fill - 1) > 0)
                echo $this->color(str_repeat('=', $fill - 1), $colorDone);
            echo $this->color(str_repeat('=', 1), $colorCurrent);
        } else {
            if ($fill > 0)
                echo $this->color(str_repeat('=', $fill), $colorDone);
        }
        if (($this->$activeProgressBar['width'] - $fill) > 0) {
            echo $this->color(str_repeat('-', $this->$activeProgressBar['width'] - $fill), $colorTotal);
        }
        $done = $this->$activeProgressBar['done'];
        $total = $this->$activeProgressBar['total'];

        //calculate remaining time
        $remainingTime = '';
        if ((($done - $this->$activeProgressBar['startProgress']) > 3) && ($total > 15)) {
            $duration = microtime(true) - $this->$activeProgressBar['startTime'];
            $unitDuration = $duration / ($done - $this->$activeProgressBar['startProgress']);
            $remainingSeconds = ceil(($total - $done) * $unitDuration);
            $remainingMinutes = floor($remainingSeconds / 60);
            if ($remainingMinutes) {
                $remainingSeconds -= $remainingMinutes * 60;
            }
            $remainingTime = ($remainingMinutes ? $remainingMinutes . 'm' : '') . $remainingSeconds . 's remaining';
        }

        echo $this->color("] {$percent}% ({$done}/{$total}) {$remainingTime}\r", $colorTotal);
    }

    public function endProgressBar() {
        if (!$this->$activeProgressBar)
            return;
        echo "\n";
        $this->$activeProgressBar = null;
    }

    /**
     *
     * Parse cli logs and change the cli colors to html tags and colors;
     * @param string $logText original log text
     * @param bool $userPre if set to false then it will use <Br /> for new line , else inserts the code inside <pre></pre>
     * @return string html text
     */
    public function logToHtml($logText, $userPre = true) {
        $textColors[self::CBLACK] = '#000000';
        $textColors[self::CDARK_GRAY] = '#505050';
        $textColors[self::CBLUE] = '#0000ff';
        $textColors[self::CLIGHT_BLUE] = '#5555ff';
        $textColors[self::CGREEN] = '#00ff00';
        $textColors[self::CLIGHT_GREEN] = '#55ff55';
        $textColors[self::CCYAN] = '#00dddd';
        $textColors[self::CLIGHT_CYAN] = '#44ffff';
        $textColors[self::CRED] = '#ff0000';
        $textColors[self::CLIGHT_RED] = '#ff5555';
        $textColors[self::CPURPLE] = '#800080';
        $textColors[self::CLIGHT_PURPLE] = '#FF0080';
        $textColors[self::CYELLOW] = '#FFFF00';
        $textColors[self::CBROWN] = '#A52A2A';
        $textColors[self::CLIGHT_GRAY] = '#808080';
        $textColors[self::CWHITE] = '#ffffff';

        $backColors[self::BMAGENTA] = '#FF00FF';
        $backColors[self::BBLACK] = '#000000';
        $backColors[self::BBLUE] = '#0000ff';
        $backColors[self::BCYAN] = '#00dddd';
        $backColors[self::BGRAY] = '#505050';
        $backColors[self::BGREEN] = '#00ff00';
        $backColors[self::BRED] = '#ff0000';
        $backColors[self::BYELLOW] = '#FFFF00';

        $patterns = array();
        $replacements = array();
        foreach ($textColors as $f => $color) {
            foreach ($backColors as $b => $background) {
                $patterns[] = '/\033\[' . $f . 'm\033\[' . $b . 'm/';
                $replacements[] = '<span style="color:' . $color . '; background:' . $background . ';">';
            }
            $patterns[] = '/\033\[' . $f . 'm/';
            $replacements[] = '<span style="color:' . $color . '">';
        }
        $patterns[] = '/\033\[0m/';
        $replacements[] = '</span>';
        $content = preg_replace($patterns, $replacements, $logText);
        if ($userPre) {
            return "<pre style='background:#000;color:#cfcfcf;font-size:11px;padding:5px;min-width:800px;overflow:auto;'>" . $content . '</pre>';
        } else {
            return nl2br($content);
        }
    }

    /**
     * Remove colors from logs so that it can be read in a text editor;
     *
     * Prepares the log for a text file;
     * @param string $logText
     * @return string
     */
    public function logToTextFile($logText) {
        return strip_tags($this->logToHtml($logText));
    }

    /**
     * Get input from cli.
     * @param string $text
     * @param string $defaultValue Value that will be returned if nothing is completed
     * @param null|string $color
     * @return string
     */
    public function input($text, $defaultValue = '', $color = null) {
        $text = $text . ($defaultValue ? ": [$defaultValue]" : ": ");
        echo $color ? $this->color($text, $color) : $text;
        return trim(trim($result = fgets(fopen('php://stdin', 'r'))) ? $result : $defaultValue);
    }

    /**
     * Get hidden input from cli.
     * @param $text
     * @return string
     */
    public function passwordInput($text) {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents($vbscript, 'wscript.echo(InputBox("' . addslashes($text) . '", "", "password here"))');
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
            return $password;
        }
        $command = "/usr/bin/env bash -c 'echo OK'";
        if (rtrim(shell_exec($command)) !== 'OK') {
            trigger_error("Can't invoke bash");
            return;
        }
        $command = "/usr/bin/env bash -c 'read -s -p \"" . addslashes($text) . "\" mypassword && echo \$mypassword'";
        $password = rtrim(shell_exec($command));
        echo "\n";
        return $password;
    }

}
