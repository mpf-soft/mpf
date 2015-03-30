<?php

namespace mpf\loggers;

use mpf\base\App;
use mpf\WebApp;

class FileLogger extends Logger{

    public $visibleLevels = array(
        Levels::EMERGENCY,
        Levels::CRITICAL,
        Levels::ALERT,
        Levels::ERROR,
        Levels::WARNING,
        Levels::NOTICE,
        Levels::INFO
    );
    /**
     * Path for file where to save the logs
     * Can use key words like {MODULE}, {CONTROLLER} for web apps and {APP_ROOT} for all.
     * @var string
     */
    public $filePath;

    protected  $_path;

    protected $_writeFailure = false;

    function getLogs() {
        return [];
        // TODO: Implement getLogs() method.
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array()) {
        if (!$this->filePath) // no file selected
            return;
        if ($this->_writeFailure){ // there are problems with writing to file; no need to keep retrying
            return;
        }
        if (!$this->_path){
            if (!is_a(App::get(), '\\mpf\\WebApp')){
                $this->_path = $this->filePath;
            } else {
                $this->_path = str_replace(['{MODULE}', '{CONTROLLER}'], [WebApp::get()->request()->getModule(), WebApp::get()->request()->getController()], $this->filePath);
            }
            $this->_path = str_replace("{APP_ROOT}", APP_ROOT, $this->_path);
        }

        if (!in_array($level, $this->visibleLevels))
            return;
        if (Levels::DEBUG == $level && isset($context['fromClass']) && in_array($context['fromClass'], $this->ignoredClasses)) {
            return;
        }

        $details = date("Y-m-d H:i ") . (isset($context['fromClass']) ? $context['fromClass'] : '') . " $message\n";
        if (in_array($level, $this->detaliedLevels)) {
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
        }
        if (false === @file_put_contents($this->_path, $details . "\n", FILE_APPEND)){
            $this->_writeFailure = true;
        }
    }
}