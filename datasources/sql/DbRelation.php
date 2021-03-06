<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 06.04.2015
 * Time: 10:26
 */

namespace mpf\datasources\sql;


use mpf\base\LogAwareObject;

class DbRelation extends LogAwareObject {
    public $name;
    public $type;
    public $model;
    public $conditions = [];
    public $joins = [];
    public $joinType = 'LEFT JOIN';
    public $order;
    public $offset;
    public $limit;
    /**
     * @var string
     */
    public $tableAlias;

    /**
     * @var string
     */
    public $tableName;

    /**
     * Creates new belongsTo Relation
     * @param string $model
     * @param string $parentColumn
     * @return static
     */
    public static function belongsTo($model, $parentColumn) {
        $r = new static(['type' => DbRelations::BELONGS_TO, 'model' => $model]);
        return $r->columnsEqual($parentColumn, $model::getDb()->getTablePk($model::getTableName()));
    }

    /**
     * Creates new hasOne relation
     * @param string $model
     * @return static
     */
    public static function hasOne($model) {
        return new static(['type' => DbRelations::HAS_ONE, 'model' => $model]);
    }

    /**
     * Creates new hasMany relation
     * @param string $model
     * @return static
     */
    public static function hasMany($model) {
        return new static(['type' => DbRelations::HAS_MANY, 'model' => $model]);
    }

    /**
     * Creates new manyToMany relation
     * @param string $model
     * @return static
     */
    public static function manyToMany($model) {
        return new static(['type' => DbRelations::MANY_TO_MANY, 'model' => $model]);
    }

    /**
     * Checks if relation is required for count.
     * @return bool
     */
    public function isRequiredForCount() {
        return 'INNER JOIN' == $this->joinType;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Set a conditions that those two columns must be equal. Also when it searches relations for more models it will
     * automatically change it in a "in" condition. If another table or relation is used  and some column can't be from
     * model or relation then it can be sent as "otherTableOrAlias.column"
     * @param string|string[] $modelColumn If a list of two or more columns is sent then it will compare c2 with this list. From main model
     * @param string|string[] $relationColumn If a list of two or more columns is sent then it will compare c1 with this list. From relation
     * @return $this
     */
    public function columnsEqual($modelColumn, $relationColumn) {
        $this->conditions[] = ["=", $modelColumn, $relationColumn];
        return $this;
    }

    /**
     * Same as columnsEqual but it will make sure that they are different.(/ NOT IN)
     * @param string|string[] $modelColumn
     * @param string|string[] $relationColumn
     * @return $this
     */
    public function columnsDifferent($modelColumn, $relationColumn) {
        $this->conditions[] = ["!=", $modelColumn, $relationColumn];
        return $this;
    }

    /**
     * It will compare selected column with the exact given value.
     * @param string $relationColumn
     * @param string|int|string[]|int[] $value
     * @return $this
     */
    public function hasValue($relationColumn, $value) {
        $this->conditions[] = ["==", $relationColumn, $value];
        return $this;
    }

    /**
     * It will compare the selected column with the value that model static attribute has at that time. That attribute must be
     * static(so that if it has to read from multiple modules it won't check the value for each of them)
     * @param string $column
     * @param string $modelAttribute can also be a method. In that case must end with () so that the script knows to call it as a method
     * @return $this
     */
    public function hasAttributeValue($column, $modelAttribute) {
        $this->conditions[] = ["=attribute", $column, $modelAttribute];
        return $this;
    }

    /**
     * Change join type
     * @param string $type
     * @return $this
     */
    public function setJoinType($type) {
        $this->joinType = strtoupper($type);
        return $this;
    }

    /**
     * @param $order
     * @return $this
     */
    public function orderBy($order) {
        $this->order = $order;
        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Creates an extra join with a new table that doesn't require a model.
     * @param string|string[string] $tableName Can be string "tableName" or [tableName=>tableAlias]
     * @param string[] $columnsCompare a list of columns to compare, example: ["t.c1" => "joinTable.c2"]
     * @param string[] $valueCompare a list of columns with exact values to compare. ["t.c1" => "value"]
     * @param string $type type of join for this table
     * @return $this
     */
    public function join($tableName, $columnsCompare, $valueCompare = [], $type = "LEFT JOIN") {
        $this->joins[] = [$tableName, $columnsCompare, $valueCompare, $type];
        return $this;
    }

    public function hasSingleResult() {
        if (in_array($this->type, [DbRelations::BELONGS_TO, DbRelations::HAS_ONE])) {
            return true;
        }
    }

    /**
     * @param DbModel $parentModel
     * @param string $fullName
     * @return string
     */
    public function getWithParent($parentModel, $fullName = 't') {
        return $this->joinType . ' ' . $this->getRelationTableForJoin($fullName) . ' ON ' . $this->getCondition($parentModel, $fullName);
    }

    protected $_lastConditionParams = [];

    /**
     * @param string $parentModel
     * @param string $fullName
     * @return string
     */
    public function getCondition($parentModel, $fullName) {
        $finalCondition = [];
        $this->_lastConditionParams = [];
        foreach ($this->conditions as $k => $condition) {
            $key = ':' . str_replace('.', '_', $fullName) . '_' . str_replace('.', '_', $condition[1]);
            switch ($condition[0]) {
                case "=":
                case "!=":
                    $finalCondition[] = $this->getColumnEqualWithParent($condition, $parentModel, $fullName);
                    break;
                case "==":
                case "=attribute":
                    if ($condition[0] == '=attribute') { // for attribute read the value
                        $c = $condition[2];
                        if (false === strpos($c, '(')) {
                            $condition[2] = $parentModel::$$c;
                        } else { // for method call it and get the value;
                            $c = substr($c, 0, strlen($c) - 2);
                            $condition[2] = call_user_func("$parentModel::$c");
                        }
                    }
                    if (is_array($condition[2])) {
                        $columns = [];
                        foreach ($condition[2] as $k => $v) {
                            $columns[] = $key . '_' . $k;
                            $this->_lastConditionParams[$key . '_' . $k] = $v;
                        }
                        $finalCondition[] = $this->_column($condition[1], false, $fullName) . ' IN (' . implode(', ', $columns) . ')';
                    } else {
                        $finalCondition[] = $this->_column($condition[1], false, $fullName) . ' = ' . $key;
                        $this->_lastConditionParams[$key] = $condition[2];
                    }
                    break;
            }
        }
        return "(" . implode(") AND (", $finalCondition) . ")";
    }

    /**
     * Returns params created by last condition;
     * @return array
     */
    public function getConditionParams() {
        return $this->_lastConditionParams;
    }


    /**
     * Creates string condition from known data
     * @param string[] $condition
     * @param string $parentModel
     * @param string $fullName
     * @return string
     */
    protected function getColumnEqualWithParent($condition, $parentModel, $fullName) {
        if (is_array($condition[1]) && is_array($condition[2])) {
            $conditions = [];
            foreach ($condition[1] as $c1) {
                $conditions[] = $this->getColumnEqualWithParent([$condition[0], $c1, $condition[2]], $parentModel, $fullName);
            }
            return "(" . implode(") OR (", $conditions) . ")";
        }
        if (is_array($condition[1])) {
            $c = $condition[1];
            $condition[1] = $condition[2];
            $condition[2] = $c;
        }
        if (is_string($condition[2])) {
            return $this->_column($condition[1], true, $fullName) . ' ' . $condition[0] . ' ' . $this->_column($condition[2], false, $fullName);//@TODO: parse columns name;
        } else {
            foreach ($condition[2] as $k => $v) {
                $condition[2][$k] = $this->_column($v, false, $fullName);
            }
            return $this->_column($condition[1], true, $fullName) . ($condition[0] == '=' ? ' IN (' : ' NOT IN (') . implode(", ", $condition[2]) . ')';
        }
    }

    protected function _column($name, $fromParent = false, $fullName = 't') {
        $parts = explode(".", $name);
        if (count($parts) == 1) {
            $parts = explode(".", $fullName);
            $column = $name;
        } else {
            $column = $parts[count($parts) - 1];
            unset($parts[count($parts) - 1]);
        }
        if ($fromParent) {
            unset($parts[count($parts) - 1]);
            if (!count($parts)) {
                $parts = ['t'];
            }
        }
        return "`" . implode("_", $parts) . "`.`$column`";
    }


    /**
     * Get table from relation join. Also calculates tableAlias;
     * @param string $fullName
     * @return string
     */
    public function getRelationTableForJoin($fullName) {
        if (!$this->tableName) {
            $class = $this->model;
            $this->tableName = $class::getTableName();
        }
        return "`" . $this->tableName . "` as  `" . ($this->tableAlias = str_replace('.', '_', $fullName)) . "`";
    }

    /**
     * Return condition for select for a single model;
     * @param DbModel $model
     * @return ModelCondition
     */
    public function getConditionForModel(DbModel $model) {
        $mCondition = new ModelCondition(['model' => $this->model]);
        $joinString = [];
        $joinParams = [];
        foreach ($this->joins as $join) {
            if (is_array($join[0])) {
                $cJoinString = $join[3] . " `" . key($join[0]) . "` as `" . current($join[0]) . '`';
                $tName = "`" . current($join[0]) . "`";
            } else {
                $cJoinString = $join[3] . " `" . $join[0] . "`";
                $tName = $join[0];
            }
            $cJoinString .= " ON ";
            $cJoinConditions = [];
            foreach ($join[1] as $column1 => $column2) { // c1 -> from parent; c2 -> from table;
                if (false === strpos($column1, '.')) { // is parent here
                    $joinParams[":t_" . $column1] = $model->$column1;
                    $column1 = ":t_" . $column1;
                }
                if (false === strpos($column2, '.')) {
                    $column2 = $tName . '.' . $column2;
                }
                $cJoinConditions[] = $column1 . ' = ' . $column2;
            }

            foreach ($join[2] as $column => $value) {
                $pName = ':';
                if (false === strpos($column, '.')) {
                    $pName .= str_replace('.', '_', "$tName.$column");
                    $cJoinConditions[] = "`$tName`.`$column` = :$pName";
                } else {
                    $pName .= str_replace('.', '_', $column);
                    $cJoinConditions[] = "$column = :$pName";
                }
                $joinParams[$pName] = $value;
            }

            $joinString[] = $cJoinString . "(" . implode(") AND (", $cJoinConditions) . ")";
        }
        if ($this->joins) {
            $mCondition->fields = "t.*";
        }
        $mCondition->setParams($joinParams);
        $mCondition->join = implode(" ", $joinString);
        foreach ($this->conditions as $ck => $condition) {
            switch ($condition[0]) {
                case "=":
                case "!=":
                    $parentColumn = $condition[1]; // for now it only supports single column for this type of relations;
                    $relationColumn = $condition[2]; // same for relation column
                    if (false !== strpos($parentColumn, '.')) {
                        if (false === strpos($relationColumn, '.')) {
                            $relationColumn = '`t`.`' . $relationColumn . '`';
                        }
                        $mCondition->addCondition("$parentColumn = $relationColumn");
                    } else {
                        if (is_array($relationColumn)) {
                            $relationColumnListForIn = [];
                            foreach ($relationColumn as $c) {
                                $relationColumnListForIn[] = $this->_column($c, true);
                            }
                            $relationColumnListForIn = implode(', ', $relationColumnListForIn);
                        } else {
                            $relationColumnListForIn = $relationColumn;
                        }
                        $conditionParts = [];
                        $paramKey = ':' . $ck . '_';
                        if (is_array($parentColumn) && is_array($relationColumn)) {
                            $columnList = [];
                            foreach ($parentColumn as $col) {
                                $mCondition->setParam($paramKey . $col, $model->$col);
                                $conditionParts[] = $paramKey . $col . ('!=' == $condition[0] ? 'NOT' : '') . ' IN ( ' . $relationColumnListForIn . ' )';
                                $columnList[] = $paramKey . $col . ('!=' == $condition[0] ? 'NOT' : '') . ' IN ( ' . $relationColumnListForIn . ' )';
                            }
                        } elseif (is_array($parentColumn)) {
                            $paramList = [];
                            foreach ($parentColumn as $col) {
                                $mCondition->setParam($paramKey . $col, $model->$col);
                                $paramList[] = $paramKey . $col;
                            }
                            $conditionParts[] = $this->_column($relationColumn, true) . ('!=' == $condition[0] ? 'NOT' : '') . ' IN (' . implode(', ', $paramList) . ')';
                        } elseif (is_array($relationColumn)) {
                            $mCondition->setParam($paramKey . $parentColumn, $model->$parentColumn);
                            $conditionParts[] = $paramKey . $parentColumn . ('!=' == $condition[0] ? 'NOT' : '') . " IN ($relationColumnListForIn)";
                        } else {
                            if (false !== strpos($parentColumn, '.')) {
                                $mCondition->compareColumns($paramKey . $relationColumn, $parentColumn);
                            } else {
                                $mCondition->setParam($paramKey . $relationColumn, $model->$parentColumn);
                                $conditionParts[] = $paramKey . $relationColumn;
                            }
                        }
                        if (is_string($parentColumn) && is_string($relationColumn)) {
                            $mCondition->addCondition($this->_column($relationColumn, true) . ('!=' == $condition[0] ? 'NOT' : '') . " IN (" . implode(', ', $conditionParts) . ")");
                        } else {
                            $mCondition->addCondition('(' . implode(") OR (", $conditionParts) . ')');
                        }
                    }
                    break;
                case "==":
                case "=attribute":
                    if ($condition[0] == '=attribute') { // for attribute read the value
                        $parentModel = get_class($model);
                        $c = $condition[2];
                        if (false === strpos($c, '(')) {
                            $condition[2] = $parentModel::$$c;
                        } else { // for method call it and get the value;
                            $c = substr($c, 0, strlen($c) - 2);
                            $condition[2] = call_user_func("$parentModel::$c");
                        }
                    }
                    $mCondition->compareColumn($condition[1], $condition[2]);
                    break;

            }
        }
        if ($this->order) {
            $mCondition->order = $this->order;
        }
        if ($this->offset) {
            $mCondition->offset = $this->offset;
        }
        if ($this->limit) {
            $mCondition->limit = $this->limit;
        }
        return $mCondition;
    }

    /**
     * @param DbModel[] $models
     * @param string|array $fields
     * @return ModelCondition
     */
    public function getConditionForModels($models, $fields) {
        $key = '__parentRelationKey';
        $column = "(CASE ";
        $mCondition = new ModelCondition(['model' => $this->model]);
        if ($this->joins) {
            foreach ($this->joins as $join) {
                if (is_array($join[0])) {
                    $t = '`' . key($join[0]) . '` as `' . current($join[0]) . '`';
                } else {
                    $t = '`' . $join[0] . '`';
                }
                $c = [];
                // columns;
                foreach ($join[1] as $col1 => $col2) {
                    if (is_array($col2)) {

                    } else {
                        if ('!=' == substr($col2, 0, 2)) {

                        } elseif ('>=' == substr($col2, 0, 2)){

                        } elseif ('<=' == substr($col2, 0, 2)){

                        } elseif ('>' == $col2[0]){

                        } elseif ('<' == $col2[0]){

                        } else {

                        }
                    }
                }
                // values;
                foreach ($join[2] as $col => $value) {
                    if (is_array($value)) {

                    } else {
                        if ('!=' == substr($value, 0, 2)) {

                        } elseif ('>=' == substr($value, 0, 2)){

                        } elseif ('<=' == substr($value, 0, 2)){

                        } elseif ('>' == $value[0]){

                        } elseif ('<' == $value[0]){

                        } else {

                        }
                    }
                }
                // type;

                $mCondition->join .= $join[3] . $t . " ON (" . implode(') AND (', $c) . ")";
            }
        }

        foreach ($this->conditions as $ck => $condition) {
            switch ($condition[0]) {
                case "=":
                case "!=":
                    $parentColumn = $condition[1]; // for now it only supports single column for this type of relations;
                    $relationColumn = $condition[2]; // same for relation column
                    if (is_string($parentColumn) && (false !== strpos($parentColumn, '.') && false !== strpos($parentColumn, '_'))) {
                        $r = $relationColumn;
                        $relationColumn = $parentColumn;
                        $parentColumn = $r;
                    }
                    if (is_array($relationColumn)) {
                        $relationColumnListForIn = [];
                        foreach ($relationColumn as $c) {
                            $relationColumnListForIn[] = $this->_column($c, true);
                        }
                        $relationColumnListForIn = implode(', ', $relationColumnListForIn);
                    } else {
                        $relationColumnListForIn = $relationColumn;
                    }
                    $conditionParts = [];
                    foreach ($models as $mk => $model) { // generates CASE column WHEN parentv1 THEN parentk1 WHEN parentv2 THEN parentk2 ELSE -1 AS __parentRelationKey  + the condition itself
                        $paramKey = ':' . $ck . '_' . $mk . '_';
                        if (is_array($parentColumn) && is_array($relationColumn)) {
                            $column .= "WHEN (";
                            $columnList = [];
                            foreach ($parentColumn as $col) {
                                $mCondition->setParam($paramKey . $col, $model->$col);
                                $conditionParts[] = $paramKey . $col . ('!=' == $condition[0] ? 'NOT' : '') . ' IN ( ' . $relationColumnListForIn . ' )';
                                $columnList[] = $paramKey . $col . ('!=' == $condition[0] ? 'NOT' : '') . ' IN ( ' . $relationColumnListForIn . ' )';
                            }
                            $column .= implode(' OR ', $columnList);
                            $column .= ") THEN $mk ";
                        } elseif (is_array($parentColumn)) {
                            $column .= "WHEN (" . $this->_column($relationColumn, true) . ('!=' == $condition[0] ? 'NOT' : '') . ' IN ';
                            $paramList = [];
                            foreach ($parentColumn as $col) {
                                $mCondition->setParam($paramKey . $col, $model->$col);
                                $paramList[] = $paramKey . $col;
                            }
                            $conditionParts[] = $this->_column($relationColumn, true) . ('!=' == $condition[0] ? 'NOT' : '') . ' IN (' . implode(', ', $paramList) . ')';
                            $column .= "(" . implode(", ", $paramList) . ")";
                            $column .= ") THEN $mk ";
                        } elseif (is_array($relationColumn)) {
                            $mCondition->setParam($paramKey . $parentColumn, $model->$parentColumn);
                            $conditionParts[] = $paramKey . $parentColumn . ('!=' == $condition[0] ? 'NOT' : '') . " IN ($relationColumnListForIn)";
                            $column .= "WHEN $paramKey . $parentColumn " . ('!=' == $condition[0] ? 'NOT' : '') . " IN ($relationColumnListForIn) THEN $mk ";
                        } else {
                            if ("(CASE " == $column)
                                $column .= $this->_column($relationColumn, true);
                            $p = str_replace('.', '__', $paramKey . $relationColumn);
                            $mCondition->setParam($p, $model->$parentColumn);
                            $conditionParts[] = $p;
                            $column .= " WHEN " . $p . " THEN $mk ";
                        }
                    }
                    if (is_string($parentColumn) && is_string($relationColumn)) {
                        $mCondition->addCondition($this->_column($relationColumn, true) . ('!=' == $condition[0] ? 'NOT' : '') . " IN (" . implode(', ', $conditionParts) . ")");
                    } else {
                        $mCondition->addCondition('(' . implode(") OR (", $conditionParts) . ')');
                    }
                    break;
                case "==":
                case "=attribute":
                    if ($condition[0] == '=attribute') { // for attribute read the value
                        $parentModel = get_class(current($models));
                        $c = $condition[2];
                        if (false === strpos($c, '(')) {
                            $condition[2] = $parentModel::$c;
                        } else { // for method call it and get the value;
                            $c = substr($c, 0, strlen($c) - 2);
                            $condition[2] = call_user_func("$parentModel::$c");
                        }
                    }
                    $mCondition->compareColumn($condition[1], $condition[2]);
                    break;
            }
        }
        $column .= "ELSE -1 END) AS `$key`";
        //die($column);
        if (is_array($fields)) {
            $fields[] = $column;
        } else {
            $fields .= ', ' . $column;
        }
        $mCondition->fields = $fields;
        return $mCondition;
    }

    /**
     * @return string
     */
    public function getTableAlias() {
        return $this->tableAlias;
    }

    /**
     * @return string
     */
    public function getTableName() {
        return $this->tableName;
    }
}