<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 02.12.2015
 * Time: 14:11
 */

namespace mpf\datasources\sql;


use mpf\base\LogAwareObject;

class Relation extends LogAwareObject {

    /**
     * declaration model:
     * 'relName' => array(\mpf\datasources\sql\DbRelations::BELONGS_TO, '\app\models\SecondModelName',  'columnName', ..$options)
     * columnName represents the name of the column from this model that has the same value as primary key from SecondModelName
     * example:
     *  'user' => array(\mpf\datasources\sql\DbRelations::BELONGS_TO, '\app\models\User', 'user_id')
     */
    const BELONGS_TO = '1';

    /**
     * declaration model:
     *  'relName' => array(\mpf\datasources\sql\DbRelations::HAS_ONE, '\app\models\SecondModelName', 'columnName', ..$options)
     * columnName represents the name of the column from SecondModelNAme that has the same value as primary key from main model
     * example:
     *  'settings' => array(\mpf\datasources\sql\DbRelations::HAS_ONE, '\app\models\UserSettings', 'user_id')
     */
    const HAS_ONE = '2';

    /**
     * declaration model:
     *  'relName' => array(\mpf\datasources\sql\DbRelations::HAS_MANY, '\app\models\SecondModelName', 'columnName', ..$options)
     * columnName represents the name of the column from SecondModelName that has the same value as primary key from main model
     * example:
     *  'logs' => array(\mpf\datasources\sql\DbRelations::HAS_MANY, '\app\models\UserLogs', 'user_id')
     */
    const HAS_MANY = '3';

    /**
     * declaration model:
     *   'relName' => array(\mpf\datasources\sql\DbRelations::MANY_TO_MANY, '\app\models\SecondModelName', 'connectiontable(main_id, relation_id)', ..$options)
     *  connectiontable represents the name of the table that holds the connections between main and relation tables
     *  main_id column name that is the connection to main table
     *  relation_id column name that is the connection to relation table
     * example:
     *   'rights' => array(\mpf\datasources\sql\DbRelations::MANY_TO_MANY, '\app\models\Rights', 'users2rights(user_id, right_id)')
     */
    const MANY_TO_MANY = '4';

    /**
     * @var string
     */
    protected $modelClass, $connection, $type, $joinType = 'LEFT JOIN';

    /**
     * @var string
     */
    protected $limit, $order, $offset, $group;

    /**
     * @var string[]
     */
    protected $compares = [], $values = [], $params = [], $joins = [];


    /**
     * Create's a belongsTo relation
     * @param string $modelClass
     * @param string $columnName
     * @param array $options
     * @return Relation
     */
    public static function belongsTo($modelClass, $columnName, $options = []) {
        $options['type'] = self::BELONGS_TO;
        $options['modelClass'] = $modelClass;
        $options['compares'] = [[(false === strpos($columnName, '.')) ? "t.$columnName" : $columnName, "r." . $modelClass::getDb()->getTablePk($modelClass::getTableName()), "="]];
        return new self($options);
    }

    /**
     * Creates a hasOne relation
     * @param $modelClass
     * @param $columnName
     * @param array $options
     * @return Relation
     */
    public static function hasOne($modelClass, $columnName, $options = []) {
        $options['type'] = self::HAS_ONE;
        $options['modelClass'] = $modelClass;
        $options['compares'] = [["t.__PK__", (false === strpos($columnName, '.')) ? "r.$columnName" : $columnName, "="]];
        return new self($options);
    }

    /**
     * Creates a hasMany relation
     * @param $modelClass
     * @param $columnName
     * @param array $options
     * @return Relation
     */
    public static function hasMany($modelClass, $columnName, $options = []) {
        $options['type'] = self::HAS_MANY;
        $options['modelClass'] = $modelClass;
        $options['compares'] = [["t.__PK__", (false === strpos($columnName, '.')) ? "r.$columnName" : $columnName, "="]];
        return new self($options);
    }

    /**
     * Creates a many2many relation
     * @param $modelClass
     * @param $connection
     * @param array $options
     * @return Relation
     */
    public static function manyToMany($modelClass, $connection, $options = []) {
        $options['type'] = self::MANY_TO_MANY;
        $options['modelClass'] = $modelClass;
        $connection = explode('(', $connection);
        $table = $connection[0];
        list($c1, $c2) = explode(',', $connection[1]);
        $r = new self($options);
        $c1 = trim($c1);
        $c2 = trim($c2);
        $r->join(trim($table), [["t.__PK__", "j.$c1", "="]])
            ->compare(["j.$c2" => "r." . $modelClass::getDb()->getTablePk($modelClass::getTableName())]);
        return $r;
    }

    /**
     * @param $table
     * @param $compare
     * @param $condition
     * @param $type
     * @param array $params
     * @return $this
     */
    public function join($table, $compare = [], $condition, $type = 'LEFT JOIN', $params = []) {
        $this->joins[] = [$table, $compare, $condition, $type, $params];
        return $this;
    }

    public function compare($columns2columns, $operator = '=', $separator = ' AND ') {
        return $this;
    }


    public function values($columns2values, $operator = '=', $separator = ' AND ') {
        return $this;
    }

    public function addCondition($condition, $params = [], $separator = ' AND ') {
        return $this;
    }

    /**
     * @var string[]
     */
    protected $_afterConditionParams, $_afterConditionJoins = [];

    /**
     * If no models are  sent will return for initial join, if not it will return a new query for selected models;
     * @param string $name - name is used to determine if it's a sub-condition or not and generate table name
     * @param null|DbModel[]|DbModel $models
     * @return string
     */
    public function getCondition($name, $models = null) {
        $this->_afterConditionParams = $this->_afterConditionJoins = [];
        if (is_null($models)) {
            return $this->_calcJoin();
        } elseif (!is_array($models)) {
            $models = [$models]; // if it's not a list already;
        }
        return $this->_calcModels($models);
    }

    /**
     * @return string
     */
    protected function _calcJoin() {
        return "";
    }

    /**
     * @param DbModel[] $models
     * @return string
     */
    protected function _calcModels($models) {
        return "";
    }

    /**
     * @return string
     */
    public function getJoin() {
        return implode(" ", $this->_afterConditionJoins);
    }

    /**
     * List of parameters to be used by query;
     * @return string[]
     */
    public function getParams() {
        return $this->_afterConditionParams;
    }


}