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
    /**
     * @var string
     */
    public $tableAlias;

    /**
     * Creates new belongsTo Relation
     * @param string $model
     * @return static
     */
    public static function belongsTo($model) {
        return new static(['type' => DbRelations::BELONGS_TO, 'model' => $model]);
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
     * @param string|int $value
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

    public function hasSingleResult(){
        if (in_array($this->type, [DbRelations::BELONGS_TO, DbRelations::HAS_ONE])){
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

    /**
     * @param string $parentModel
     * @param string $fullName
     * @return string
     */
    public function getCondition($parentModel, $fullName) {
        $finalCondition = [];
        foreach ($this->conditions as $condition) {
            switch ($condition[0]) {
                case "=":
                case "!=":
                    $finalCondition[] = $this->getColumnEqualWithParent($condition, $parentModel, $fullName);
                    break;
                case "==":

                    break;
                case "=attribute":
                    break;
            }
        }
        return "(" . implode(") AND (", $finalCondition) . ")";
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
            return $this->_column($condition[1]) . ' ' . $condition[0] . ' ' . $condition[2];//@TODO: parse columns name;
        } else {
            return $condition[1] . ($condition[0] == '=' ? ' IN (' : ' NOT IN (') . implode(", ", $condition[2]) . ')';
        }
    }

    protected function _column($name, $fromParent = false) {
        $parts = explode(".", $name);
        $column = $parts[count($parts) - 1];
        unset($parts[count($parts) - 1]);
        if ($fromParent) {
            unset($parts[count($parts) - 1]);
        }
        return "`" . implode("_", $parts) . "`.`$column`";
    }


    /**
     * Get table from relation join. Also calculates tableAlias;
     * @param string $prefix
     * @return string
     */
    public function getRelationTableForJoin($prefix) {
        $class = $this->model;
        $table = $class::getTableName();
        return $table . " as  " . ($this->tableAlias = ("t" == $prefix ? $this->name : $prefix . '_' . $this->name));
    }
}