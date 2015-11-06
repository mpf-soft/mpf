<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 05.11.2015
 * Time: 11:26
 */

namespace mpf\loggers;


use GuzzleHttp\Message\MessageFactory;
use mpf\base\App;

class EmailLogger extends Logger {
    /**
     * Class used to send emails;
     * @var string
     */
    public $mailerClass = 'mpf\helpers\MailHelper';

    /**
     * Developer email address
     * @var string
     */
    public $emailAddress;

    /**
     * Email subject prefix; If none is set then app short name will be used;
     * @var string
     */
    public $emailPrefix;

    /**
     * Final title that apppears after info in subject;
     * @var string
     */
    public $emailTitle = 'Application Log';

    public $visibleLevels = [
        Levels::EMERGENCY,
        Levels::CRITICAL,
        Levels::ALERT,
        Levels::ERROR,
        Levels::WARNING,
        Levels::NOTICE
    ];

    public function getLevelMessageColor($lvl) {
        $t = [
            Levels::EMERGENCY => 'orangered',
            Levels::CRITICAL => 'orangered',
            Levels::ALERT => 'orangered',
            Levels::ERROR => 'orangered',
            Levels::WARNING => 'orangered',
            Levels::NOTICE => 'orangered',
            Levels::INFO => 'limegreen',
            Levels::DEBUG => 'blue'
        ];
        return $t[$lvl];
    }

    /**
     * @param string $lvl
     * @return array|string
     */
    public function getLevelTranslations($lvl = null) {
        $t = [
            Levels::EMERGENCY => 'Emergency',
            Levels::CRITICAL => 'Critical',
            Levels::ALERT => 'Alert',
            Levels::ERROR => 'Error',
            Levels::WARNING => 'Warning',
            Levels::NOTICE => 'Notice',
            Levels::INFO => 'Info',
            Levels::DEBUG => 'Debug'
        ];
        return $lvl ? $t[$lvl] : $t;
    }

    /**
     * No return from this;
     * @return array
     */
    public function getLogs() {
        return [];
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = []) {
        if (in_array($level, $this->visibleLevels) && $this->emailAddress) {
            $mailer = $this->mailerClass;
            $mailer::get()->send($this->emailAddress, $this->getSubject($level, isset($context['fromClass']) ? $context['fromClass'] : ''), $this->getMessage($level, $message, $context));
        }
    }

    protected function getSubject($level, $class) {
        $class = $class ? "[$class]" : "";
        return ($this->emailPrefix ?: '[' . App::get()->shortName . '] ') . '[' . date('Y-m-d H:i') . '] ' . $class . ' [' . $this->getLevelTranslations($level) . '] ' . $this->emailTitle;
    }

    protected function getMessage($level, $message, $context) {
        unset($context['fromClass']);
        $context = implode("<br />", $this->getContextLines($context));
        $message = <<<MESSAGE
<h3 style="color:{$this->getLevelMessageColor($level)}">$message</h3>
<div style="border: 1px solid #888; background: #cfcfdf; color:#444; line-height: 20px; padding:5px;">$context</div>

MESSAGE;
        $lines = [];
        if (ltrim(get_class(App::get()), '\\') == 'mpf\WebApp') {
            $lines[] = '<b>URL:</b> http' . (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $lines[] = '<b>Referer:</b> ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-');
            $lines[] = '<b>POST:</b> ' . (isset($_POST) ? $this->getArrayList($_POST) : '-');
            $lines[] = '<b>SESSION:</b> ' . (isset($_SESSION) ? $this->getArrayList($_SESSION) : '-');
        } elseif (ltrim(get_class(App::get()), '\\') == 'mpf\ConsoleApp') {
            $lines[] = '<b>Command:</b> ' . implode(' ', $_SERVER['argv']);
            $lines[] = '<b>User:</b> ' . exec('whoami');
        }
        return $message . implode("<br />\n", $lines);
    }

    protected function getContextLines($context, $prefix = '') {
        $lines = [];
        foreach ($context as $k => $v) {
            if (is_string($v) || is_numeric($v)) {
                if ('Trace' == $k) {
                    $lines[] = "$prefix $k:";
                    $lines[] = "$prefix | " . str_replace("\n", "<br />{$prefix} | ", htmlentities($v));
                } else {
                    $lines[] = "$prefix $k => " . $v . "\n";
                }
            } elseif (is_bool($v)) {
                $lines[] .= "$prefix $k => " . ($v ? 'true' : 'false') . "\n";
            } elseif (is_array($v)) {
                $lines = array_merge($lines, $this->getContextLines($v, $prefix . "   "));
            } elseif (is_a($v, '\Exception')) {
                /* @var $v \Exception */
                $lines[] = "$prefix Exception: ";
                $lines[] = "$prefix | Location: " . $v->getFile() . ' [' . $v->getLine() . ']:';
                $lines[] = "$prefix | " . str_replace("#", "<br />\n{$prefix} | ", htmlentities($v->getTraceAsString()));
            } else {
                $lines[] = "$prefix $k => " . print_r($v, true);
            }
        }
        return $lines;
    }

    /**
     * @param $list
     * @param string $separator
     * @return string
     */
    protected function getArrayList($list, $separator = '; ') {
        $r = [];
        foreach ($list as $k => $v) {
            $r[] = "$k: " . print_r($v, true);
        }
        return implode($r, $separator);
    }
}
