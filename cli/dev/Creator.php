<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 24.09.2014
 * Time: 17:04
 */

namespace mpf\cli\dev;


use mpf\base\App;
use mpf\base\AutoLoader;
use mpf\base\LogAwareObject;
use mpf\datasources\sql\DbRelations;
use mpf\WebApp;

class Creator extends LogAwareObject {

    public $table, $class, $columns, $labels, $namespace, $relations;

    public function model() {
        $date = date('Y-m-d');
        $time = date('H:i');
        $model = <<<MODEL
<?php
/**
 * Created by MPF Framework.
 * Date: {$date}
 * Time: {$time}
 */

namespace {$this->namespace};

use mpf\datasources\sql\DataProvider;
use mpf\datasources\sql\DbModel;
use mpf\datasources\sql\DbRelations;
use mpf\datasources\sql\ModelCondition;

/**
 * Class {$this->class}
 * @package {$this->namespace}
{$this->getModelProperties()}
 */
class {$this->class} extends DbModel {

    /**
     * Get database table name.
     * @return string
     */
    public static function getTableName() {
        return "{$this->table}";
    }

    /**
     * Get list of labels for each column. This are used by widgets like form, or table
     * to better display labels for inputs or table headers for each column.
     * @return array
     */
    public static function getLabels() {
        return array(
             {$this->getModelLabels()}
        );
    }

    /**
     * Return list of relations for current model
     * @return array
     */
    public static function getRelations(){
        return array(
             {$this->getModelRelations()}
        );
    }

    /**
     * List of rules for current model
     * @return array
     */
    public static function getRules(){
        return array(
            array("{$this->getColumnsList(false)}", "safe", "on" => "search")
        );
    }

    /**
     * Gets DataProvider used later by widgets like \mpf\widgets\datatable\Table to manage models.
     * @return \mpf\datasources\sql\DataProvider
     */
    public function getDataProvider() {
        \$condition = new ModelCondition(array('model' => __CLASS__));

        foreach (array({$this->getColumnsList(true)}) as \$column) {
            if (\$this->\$column) {
                \$condition->compareColumn(\$column, \$this->\$column, true);
            }
        }
        return new DataProvider(array(
            'modelCondition' => \$condition
        ));
    }
}

MODEL;
        echo $model;
        $prefixes = App::get()->autoload()->getPrefixesPsr4();
        $found = false;
        foreach ($prefixes as $pref => $paths){
            if (0 === strpos($this->namespace, $pref)){
                $path = $paths[0] . '/' . substr($this->namespace, strlen($pref));
                $found = true;
                break;
            }
        }
        if (!$found){
            $this->error("Path for {$this->namespace} not found!");
            return false;
        }
        $path .= '/' . $this->class . '.php';
        $this->debug('File name: ' . $path);
        if (file_exists($path)) {
            $this->error('File already exists!');
            return false;
        }
        file_put_contents($path, $model);
    }

    protected function getColumnsList($array){
        if (!$array){
            return implode(", ", array_keys($this->columns));
        }
        $list = array();
        foreach ($this->columns as $name=>$type){
            $list[] = "\"$name\"";
        }
        return implode(", " , $list);
    }

    protected function getModelRelations() {
        if (!$this->relations) {
            return '';
        }
        $result = array();
        $types = array(
            DbRelations::BELONGS_TO => 'DbRelations::BELONGS_TO',
            DbRelations::HAS_ONE => 'DbRelations::HAS_ONE',
            DbRelations::HAS_MANY => 'DbRelations::HAS_MANY',
            DbRelations::MANY_TO_MANY => 'DbRelations::MANY_TO_MANY'
        );
        foreach ($this->relations as $relation) {
            $type = $types[$relation['type']];
            $result[] = "'{$relation['name']}' => array($type, '{$relation['model']}', '{$relation['connection']}')";
        }

        return implode(",\n             ", $result);
    }

    protected function getModelProperties() {
        $result = array();
        foreach ($this->columns as $name => $type) {
            $stype = 'string';
            if ('int' == substr($type, 0, 3) || 'smallint' == substr($type, 0, 8) || 'tinyint' == substr($type, 0, 7)) {
                $stype = 'int';
            }
            $result[] = " * @property $stype \${$name}";
        }
        if ($this->relations) {
            foreach ($this->relations as $relation) {
                $multiple = in_array($relation['type'], array(DbRelations::HAS_MANY, DbRelations::MANY_TO_MANY)) ? '[]' : '';
                $result[] = " * @property {$relation['model']}$multiple \${$relation['name']}";
            }
        }
        return implode("\n", $result);
    }

    protected function getModelLabels() {
        $result = array();
        foreach ($this->labels as $column => $label) {
            $result[] = "'$column' => '$label'";
        }
        return implode(",\n             ", $result);
    }
} 