<?php

namespace mpf;

/**
 * Default class used for terminal applications created with MPF Framework. Most of the times this will be used for the cronjobs that are required by  the project.
 *
 * To execute a terminal application you need the following command:
 * [code=bash]php /path/to/app/folder/php/run.php command action --extraParam=value[/code]
 *
 * The `command` part represents the class name of an extended {@class:\mpf\cli\Command} class. They can be find, by default, in `php/commands/` folder.
 * Each command can have multiple actions that should be name like this: `"actionName"` . When this is called only the `"name"` part is used.
 *
 * In the example that I used above, it will call the `actionAction()` method inside the `Command` class. Also, if the `actionAction()` method has a parameter
 * with the name `$extraParam` then it will use `"value"` for it when calling the method.
 *
 * More details about this process can be found on the {@class:\mpf\cli\Command} class description page.
 *
 * You can run a `ConsoleApp` similar to any other app:
 * [php]
 * ConsoleApp::run(array(
 * 'startTime' => microtime(true),
 * 'autoload' => $autoload
 * ));
 * [/php]
 *
 * It will offer access to the same components that {@class:\mpf\base\App} offers.
 *
 */
class ConsoleApp extends base\App {

    /**
     * List of aliases for different commands
     *   aliasName => className
     * @var array
     */
    public $commandAliases = array(
        'dev' => '\mpf\cli\dev\Command'
    );

    /**
     * Name of namespace && folder for commands;
     * @var string
     */
    public $commandsNamespace = 'commands';

    public $notFoundCommand = '\\mpf\\cli\\NotFound';

    protected function start() {
        $args = $this->getServerArguments();
        if ($args['command']) {
            $commandClass = $this->getCommandClassFromNameAndModule($args['command'], null);
        } else {
            $commandClass = $this->notFoundCommand;
        }
        if (!class_exists($commandClass)) {
            $this->alert('Command ' . $commandClass . ' not found!', array(
                'requestedCommand' => $args['command'],
                'requestedModule' => '-'
            ));
            $commandClass = $this->notFoundCommand;
        }

        $command = $this->loadCommand($commandClass);
        $command->setActiveAction($args['action']);
        $command->run($args['params']);
    }

    /**
     * Reads and returns a list with all arguments. Return strucuture:
     *  array(
     *      'command' => COMAND_NAME,
     *      'action' => ACTION_NAME
     *      'params' => array(
     *           'name1'=>'value1',
     *           'name2'=>'value2'
     *      )
     *  )
     * @return string[]
     */
    private function getServerArguments() {
        $args = $_SERVER['argv'];
        array_shift($args);

        $args = $this->parseArgs($args);

        return $args;
    }

    private function parseArgs($args) {
        $command = $action = null;
        $params = array();
        foreach ($args as $argument) {
            if ('-' != $argument[0])
                if (null === $command)
                    $command = $argument;
                elseif (null === $action)
                    $action = $argument;
                else
                    $this->alert('Invalid argument ' . $argument . '!', array('command' => $command, 'action' => $action, 'serverArguments' => $_SERVER['argv'], 'processedArguments' => $args));
            else {
                $argument = substr($argument, 1); // supports both  one - or two - ( - / -- )
                if ('-' == $argument[0])
                    $argument = substr($argument, 1);

                $argument = explode('=', $argument, 2);
                $params[$argument[0]] = isset($argument[1]) ? $argument[1] : true;
            }
        }
        return array(
            'command' => $command,
            'action' => $action,
            'params' => $params
        );
    }

    /**
     * Set a callable method for when a signal it's sent to this process.
     *
     * @param int $sigNumber
     * @param callable $handler
     * @param boolean $restart_syscall
     * @return boolean
     */
    public function setSignalCallback($sigNumber, $handler, $restart_syscall = true) {
        return pcntl_signal($sigNumber, $handler, $restart_syscall);
    }

    /**
     * Returns full namespace and classname for selected command.
     * Command name is modified with ucfirst() method. Also 'app' it's
     * added as a vendor name in namespace and $this->commandsNamespace
     * as  subnamespace. In case of modules, if there are no aliases for
     * selected module then modulesNamespace it's added and then module name  plus
     * commandsNamespace, in case an alias it's found, then that alias it's
     * used instead of 'app', modulesNamespace and module name .
     *
     * Examples:
     *   Command: home
     *   Module : -
     *   Result : \app\commands\Home
     *
     *   Command: home
     *   Module: admin
     *   Result: \app\modules\admin\commands\Home
     *
     *   Command: home
     *   Module: chat
     *   Alias for chat: outsidevendor\chatModule
     *   Result: \outsidevender\chatModule\commands\Home
     *
     *
     * @param string $command name of the command
     * @param string $module name of the module
     * @return string
     */
    public function getCommandClassFromNameAndModule($command, $module) {
        if (isset($this->commandAliases[$command])) { // check if is an alias first.
            return $this->commandAliases[$command];
        }
        if (!$module) {
            return '\\app\\' . $this->commandsNamespace . '\\' . ucfirst($command);
        } elseif (isset($this->modulesAliases[$module])) {
            return '\\' . $this->modulesAliases[$module] . '\\' . $this->commandsNamespace . '\\' . ucfirst($command);
        }
        return '\\app\\' . $this->modulesNamespace . '\\' . $module . '\\' . $this->commandsNamespace . '\\' . ucfirst($command);
    }

    /**
     * Instantiate command and check if class is correct;
     * @param string $class
     * @return \mpf\cli\Command
     */
    private function loadCommand($class) {
        $controller = new $class();
        if (!is_a($controller, '\\mpf\\cli\\Command')) {
            $this->critical('Command `' . $class . '` must extend \\mpf\\cli\\Command!', array(
                'requestedController' => $this->request()->getController(),
                'requestedModule' => $this->request()->getModule()
            ));
            return null;
        }
        return $controller;
    }

}
