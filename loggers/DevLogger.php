<?php

namespace mpf\loggers;

use mpf\web\AssetsPublisher;
use mpf\web\helpers\Html;
use mpf\WebApp;

class DevLogger extends Logger {

    public $ignoreLevels = array();

    protected $registered = false;
    protected $logs = array();

    /**
     * If is set to true then it will skip shutdown function. (so that it won't affect file downloads and similar stuff)
     * @var bool
     */
    public static $ignoreOutput = false;


    protected function prepareException(\Exception $exception) {
        $details = array();
        $details['exceptionClass'] = get_class($exception);
        $details['code'] = $exception->getCode();
        $details['line'] = $exception->getLine();
        $details['file'] = $exception->getFile();
        $details['trace'] = $exception->getTrace();
        $details['stringTrace'] = Html::get()->encode($exception->getTraceAsString());
        if ($exception->getPrevious()) {
            $details['previous'] = $this->prepareException($exception->getPrevious());
        }
        return $details;
    }

    public function log($level, $message, array $context = array()) {
        if (in_array($level, $this->ignoreLevels)) {
            return;
        }
        if (isset($context['exception'])) {
            $context['exception'] = $this->prepareException($context['exception']);
        } elseif (isset($context['Trace'])){
            $context['Trace'] = Html::get()->encode($context['Trace']);
        }
        $context['logTime'] = date('H:i:s') . '.' . substr(microtime(), 2, 5);
        $this->logs[] = array('level' => $level, 'message' => Html::get()->encode($message), 'context' => $context);
        if (!$this->registered) {
            $this->registered = true;
            $_self = $this;
            register_shutdown_function(function () use ($_self) {
                $_self->onShutDown();
            });
        }
        if ($level == Levels::ERROR) {
        }
    }

    public function getLogs() {

    }

    public function onShutDown() {
        if (WebApp::get()->request()->isAjaxRequest()|| self::$ignoreOutput) {
            return;
        }
        $url = AssetsPublisher::get()->publishFolder(__DIR__ . DIRECTORY_SEPARATOR . 'devloggerassets/');
        $json = json_encode($this->logs);
        $time = number_format(microtime(true) - WebApp::get()->startTime, 4);
        echo Html::get()->cssFile($url . 'style.css') .
            Html::get()->mpfScriptFile('jquery.js') .
            Html::get()->scriptFile($url . 'script.js') .
            Html::get()->script("var DevLogger_RunTime = $time; \n" .
                "var DevLogger_Logs = " . $json);
    }

}
