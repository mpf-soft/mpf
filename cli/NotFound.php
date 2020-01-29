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

use \mpf\cli\Helper as HCli;
use mpf\ConsoleApp;

class NotFound extends Command {

    public function actionIndex() {
        $this->error('Command not set or not found!');
        $commands = $this->getAllCommands();
    }

    protected function getAllCommands() {
        $path = APP_ROOT . str_replace('\\', '/', ConsoleApp::get()->commandsNamespace) . DIRECTORY_SEPARATOR;
        if (!$path) {
            return [];
        }
        $files = scandir($path);
        echo HCli::color('Available actions: ' . "\n\n");
        foreach ($files as $file) {
            if (in_array($file, array('.', '..', '.htaccess'))) {
                continue;
            }
            $command = lcfirst(str_replace('.php', '', $file));
            $reflection = new \ReflectionClass($this->getClassFromFile($file));
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                $this->present($command, $method, $reflection);
            }
        }
        echo "\n";
    }

    /**
     * Echo how an action can be called from cli command;
     * @param string $command
     * @param \ReflectionMethod $method
     * @return null
     */
    protected function present($command, \ReflectionMethod $method, \ReflectionClass $class) {
        if ($method->isStatic()) {
            return;
        }
        if (strpos($method->getName(), 'action') !== 0) {
            return;
        }

        $params = $method->getParameters();

        echo $this->getRunPath() . ' ' . $command . ' ' . lcfirst(substr($method->getName(), 6));

        foreach ($params as $p) {
            echo $this->paramDetails($p);
        }
        if ($class->getDocComment()) {
            echo "\n" . $class->getDocComment();
        }
        echo "\n\n";
    }

    /**
     * Get namespace for commands;
     * @return string
     */
    protected function getNameSpace(): string
    {
        return '\\app\\' . \mpf\ConsoleApp::get()->commandsNamespace . '\\';
    }

    /**
     * Get full class name(with namespace) from filename;
     * @param string $fileName
     * @return string
     */
    protected function getClassFromFile(string $fileName): string
    {
        return self::getNameSpace() . str_replace('.php', '', $fileName);
    }

    /**
     * Get path to script as it was executed;
     * @return string
     */
    protected function getRunPath(): string
    {
        return $_SERVER['SCRIPT_NAME'];
    }

    /**
     * Get a declaration of the param for cli command;
     * @param \ReflectionParameter $param
     * @return string
     * @throws \ReflectionException
     */
    protected function paramDetails(\ReflectionParameter $param): string
    {
        return ' ' . ($param->isOptional() ? '[' : '') . '--' . $param->getName() . ($param->isOptional() ? '=' . ($param->isDefaultValueConstant() ? $param->getDefaultValueConstantName() : '"' . $param->getDefaultValue() . '"') : '') . ($param->isOptional() ? ']' : '');
    }

}
