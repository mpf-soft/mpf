<?php

namespace mpf\cli\dev;

use mpf\cli\Helper;
use mpf\ConsoleApp;

/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 24.09.2014
 * Time: 12:21
 */
class Command extends \mpf\cli\Command {
    public function actionIndex() {
        echo "Actions:

model --table='table_name'   (you will be asked for more details later)
controller --name='Class'
module --name='moduleName'

";

    }

    protected function similarTables($table) {
        $similar = ConsoleApp::get()->sql()->queryAssoc("SHOW TABLES LIKE '%$table%'");
        echo "Similar tables: \n";
        $tables = array();
        foreach ($similar as $row) {
            foreach ($row as $table) {
                $tables[] = $table;
                echo '[' . count($tables) . '] ' . $table . "\n";
            }
        }
        $firstTry = true;
        do {
            if (!$firstTry) {
                $this->error('Invalid selection!');
            }
            $table = Helper::get()->input("Select another table (enter number or 0 to exit)", 0);
            if (!$table) {
                $this->debug("Aborded!");
                return false;
            }
            $firstTry = false;
        } while (!isset($tables[$table + 1]));
        return $tables[$table - 1];
    }

    public function actionModel($table) {
        if (!ConsoleApp::get()->sql()->tableExists($table)) {
            $this->error("Table `$table` not found!");
            if (!($table = $this->similarTables($table))) {
                return;
            }
        }
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
        $className = Helper::get()->input("Class name", $className);
        $this->info("Selected class: $className");
        $columns = ConsoleApp::get()->sql()->getTableColumns($table);
        $modelInfo = array(
            'table' => $table,
            'class' => $className,
            'namespace' => Helper::get()->input("Namespace", 'app\models'),
            'columns' => array(),
            'labels' => array()
        );
        echo "Labels (found " . count($columns) . " columns): \n";
        foreach ($columns as $column) {
            $modelInfo['columns'][$column['Field']] = $column['Type'];
            $modelInfo['labels'][$column['Field']] = Helper::get()->input($column['Field'], ucwords(str_replace('_', ' ', $column['Field'])));
        }

        if ('Y' == strtoupper(Helper::get()->input('Add relations? (y/n)', 'y'))) {
            $modelInfo['relations'] = array();
            do {
                $name = '';
                while (!$name) {
                    $name = Helper::get()->input('Name');
                }
                $type = 0;
                while (!$type) {
                    $type = Helper::get()->input('Types
[1] : BELONGS_TO
[2] : HAS_ONE
[3] : HAS_MANY
[4] : MANY_TO_MANY

Selected type');
                }
                $model = '';
                $classError = false;
                while (!$model || ($classError = !class_exists($model))) {
                    if ($classError){
                        $this->error('Class "'.$model.'" not found!');
                        $classError = false;
                    }
                    $model = Helper::get()->input('Model class');
                }
                $connection = '';
                while (!$connection) {
                    $connection = Helper::get()->input('Connection');
                }
                $modelInfo['relations'][] = array(
                    'name' => $name,
                    'type' => $type,
                    'model' => $model,
                    'connection' => $connection
                );
            } while ('y' == Helper::get()->input('Add more? (y/n)', 'y'));
        }

        $creator = new Creator($modelInfo);
        if ($creator->model()) {
            $this->debug('model created!');
        }
    }

    public function actionController($name) {

    }

    public function actionModule($name) {

    }
} 