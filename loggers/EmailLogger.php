<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 05.11.2015
 * Time: 11:26
 */

namespace mpf\loggers;


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

    public $visibleLevels = [
        Levels::EMERGENCY,
        Levels::CRITICAL,
        Levels::ALERT,
        Levels::ERROR,
        Levels::WARNING,
        Levels::NOTICE
    ];

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
            $mailer = $mailer::get();
            /* @var $mailer \mpf\helpers\MailHelper */
            $mailer->send($this->emailAddress, $this->getSubject($level), $this->getMessage($level, $message, $context));
        }
    }

    protected function getSubject($level) {
        return ($this->emailPrefix ?: '[' . App::get()->shortName . '] Application Log: ') . $this->getLevelTranslations($level);
    }

    protected function getMessage($level, $message, $context) {
        $lines = [date('Y-m-d H:i:s') . ' [' . $this->getLevelTranslations($level) . '] [' . (isset($context['fromClass']) ? $context['fromClass'] : '-') . ']'];
        $lines[] = $message;
        if (ltrim(get_class(App::get()), '\\') == 'mpf\WebApp') {
            $lines[] = '';
            $lines[] = "WebApp:";
            $lines[] = '<b>URL:</b> http' . (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $lines[] = '<b>POST:</b> ' . (isset($_POST) ? $this->getArrayList($_POST) : '-');
            $lines[] = '<b>SESSION:</b> ' . (isset($_SESSION) ? $this->getArrayList($_SESSION) : '-');
            $lines[] = '================================';
            $lines[] = '';
        } elseif (ltrim(get_class(App::get()), '\\') == 'mpf\ConsoleApp') {
            $lines[] = '';
            $lines[] = 'ConsoleApp:';
            $lines[] = '<b>Command:</b> ' . implode(' ', $_SERVER['argv']);
            $lines[] = '<b>User:</b> ' . exec('whoami');
            $lines[] = '================================';
            $lines[] = '';
        }
        unset($context['fromClass']);
        $lines = array_merge($lines, $this->getContextLines($context));
        return implode("<br />\n", $lines);
    }

    protected function getContextLines($context, $prefix = '') {
        $lines = [];
        foreach ($context as $k => $v) {
            if (is_string($v) || is_numeric($v)) {
                $lines[] = "$prefix $k => " . $v . "\n";
            } elseif (is_bool($v)) {
                $lines[] .= "$prefix $k => " . ($v ? 'true' : 'false') . "\n";
            } elseif (is_array($v)) {
                $lines = array_merge($lines, $this->getContextLines($v, $prefix . "   "));
            } elseif (is_a($v, '\Exception')) {
                /* @var $v \Exception */
                $lines[] = "$prefix Exception: ";
                $lines[] = "$prefix | Location: " . $v->getFile() . ' [' . $v->getLine() . ']:';
                $lines[] = "$prefix | " . $v->getTraceAsString();
            } else {
                $lines[] = "$prefix $k => " . print_r($v, true);
            }
        }
        return [];
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