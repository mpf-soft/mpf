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

namespace mpf\datasources\sql;
use mpf\base\LogAwareObject;


/**
 * Description of ModelCondition
 *
 * @author mirel
 */
class ModelCondition extends LogAwareObject {

    /**
     * Model class for current table;
     * It's optional, conditions can be created outside the model also;
     * @var string
     */
    public $model;

    /**
     * Offset from wich to start reading/updating
     * @var int
     */
    public $offset = 0;

    /**
     * Maximum number of elements to be returned;
     * @var int
     */
    public $limit;

    /**
     * String sql ready condition;
     * @var string
     */
    public $condition;

    /**
     * Column or expression used for order;
     * @var string
     */
    public $order;

    /**
     * Having condition; Can be used in addition to group
     * @var string
     */
    public $having;

    /**
     * Column or expression to be used for grouping
     * @var string
     */
    public $group;

    /**
     * An array of relation names or a string with relation names separated
     * by ",";
     * Can only be used from a model.
     * @example "child1, child2, child2.subchild2"
     * @var mixed
     */
    public $with = [];

    /**
     * Fields to be selected; Can be an array of field or a string with columns /
     * expressions separated by ,
     * @var mixed
     */
    public $fields = '*';

    /**
     * Extra joins can be manually written here. Can be used when there is no
     * model but relations are still needed in the query;
     * @var string
     */
    public $join = '';

    /**
     * Set procedure name and arguments for select;
     * @link https://dev.mysql.com/doc/refman/5.0/en/select.html
     * @var string
     */
    public $procedure;

    /**
     * Possible options:
     *  OUTFILE 'file_name' export options
     *  DUMPFILE 'file_name'
     *  var_name [, var_name]
     * @link https://dev.mysql.com/doc/refman/5.0/en/select.html
     * @var string
     */
    public $into = '';

    /**
     * If set to true "FOR UPDATE" will be added to query;
     * @var boolean
     */
    public $for_update;

    /**
     * If set to true "LOCK IN SHARE MODE" will be added to query;
     * @var boolean
     */
    public $lock_in_share_mode;

    /**
     * If set to true will be used for INSERT & UPDATE queries;
     * @var boolean
     */
    public $ignore;

    /**
     * Select multiple conditions(HAS_MANY and MANY_TO_MANY) in the same query for all models.
     * If it's set to no then it will be selected for each model when is used. In that case
     * it will be added in with only for the conditions to work.
     * @var bool
     */
    public $together = false;

    /**
     * List of parameters linked to current condition;
     * @var string[string]
     */
    protected $params = array();

    /**
     * List of relations used in columns conditions
     * @var array
     */
    protected $relationsInCondition = array();

    /**
     * List of known columns used in condition. Used to know which relations to include.
     * @var array
     */
    protected $conditionColumns = array();

    /**
     * Return list of params and the values.
     * @return string[string]
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Get full select query from current condition;
     * @return string
     */
    public function __toString() {
        return $this->getAsQuery();
    }

    /**
     * Get as string query.
     * @param bool $forCount
     * @return string
     */
    public function getAsQuery($forCount = false){
        $table=$model=null;
        if ($this->model) {
            $model = $this->model;
            $table = $model::getTableName();
        }
        $join = $this->getJoin($forCount);
        $group = ($group = $this->getGroup()) ? ' GROUP BY ' . $group : '';
        $having = ($having = $this->getHaving()) ? ' HAVING ' . $having : '';
        $order = ($order = $this->getOrder()) ? ' ORDER BY ' . $order : '';
        $limit = ($limit = $this->getLimit()) ? ' LIMIT ' . $limit : '';
        $procedure = ($procedure = $this->getProcedure()) ? ' PROCEDURE ' . $procedure : '';
        $into = ($into = $this->getInto()) ? ' INTO ' . $into : '';
        $for_update = $this->for_update ? ' FOR UPDATE' : '';
        $lock_in_share_mode = $this->lock_in_share_mode ? ' LOCK IN SHARE MODE' : '';
        $q = ($this->model ? "SELECT " . $this->getSelect() . " FROM `$table` as `t` " : "") . $join . " WHERE " . $this->getCondition() . $group . $having . $order . $limit . $procedure . $into . $for_update . $lock_in_share_mode;
        return $q;
    }

    /**
     * Get full select query for count;
     * @return string
     */
    public function getCountQuery(){
        return $this->__toString(true);
    }

    /**
     * Get query For Delete
     * @param array $deleteOptions Possible Options: LOW_PRIORITY, QUICK or IGNORE
     * @return string
     */
    public function forDelete($deleteOptions = []){
        $table=$model=null;
        if ($this->model) {
            $model = $this->model;
            $table = $model::getTableName();
        }
        $join = $this->getJoin();
        $order = ($order = $this->getOrder()) ? ' ORDER BY ' . $order : '';
        $limit = ($limit = $this->getLimit()) ? ' LIMIT ' . $limit : '';
        $options = implode(" ", $deleteOptions);
        $q = ($this->model ? "DELETE $options FROM `t` USING `$table` as `t` " : "") . $join . " WHERE (" . $this->getCondition() . ")" . $order . $limit;
        return $q;
    }

    /**
     * Compare a colum with a single value or multiple values;
     * Example:
     *  [php]
     *  $condition->compareColumn('name', 'Mirel'); // simple comparison with exact value.
     *    //  Result: "`tableName`.`name` = :name" where :name = 'Mirel'
     *  $condition->compareColumn('name', array('Mirel', 'Nicu')); // compare with multiple values;
     *    //  Result: "`tableName`.`name` IN (:name1, :name2)" where :name1 = 'Mirel' and :name2 = 'Nicu'
     *  $condition->compareColumn('age', '>=20'); // must be equal or bigger than selected value
     *    //  Result: "`tableName`.`age` >= :age" // where :age = 20
     *  $condition->compareColumn('name', 'Mirel', true); // partial match
     *    //  Result: "`tableName`.`name` LIKE :name" //where :name = '%Mirel%'
     *  [/php]
     * @param string $name column name to be compare. if it's from a relation then it's relationName.columnName
     * @param string|int|string[]|int[] $value value to compare with; Can start with: >, <, >=, <=, !=. Can also be an array, in that case "IN" will be used as condition
     * @param boolean $partial if it's set to true then "LIKE" will be used with "%" added before and after the value;
     * @param string $link Connection between this condition and current one. Can be AND | OR
     * @return \mpf\datasources\sql\ModelCondition
     */
    public function compareColumn($name, $value, $partial = false, $link = 'AND') {
        $this->conditionColumns[] = $name;
        if (is_array($value)) {
            $values = array();
            foreach ($value as $val) {
                $values[] = $this->_param($name, $partial ? '%' . $val . '%' : $val, true);
            }
            return $this->addCondition($this->_column($name) . ' IN (' . implode(', ', $values) . ")", array(), $link);
        }
        if ($partial)
            return $this->addCondition($this->_column($name) . ' LIKE  ' . $this->_param($name, "%$value%"), array(), $link);

        $operators = array('>=', '<=', '!=', '>', '<');
        $matched = false;
        foreach ($operators as $operator) {
            if ($operator == substr($value, 0, strlen($operator))) {
                $value = trim(substr($value, strlen($operator)));
                $matched = true;
                break;
            }
        }
        $operator = $matched ? $operator : '=';
        return $this->addCondition($this->_column($name) . ' ' . $operator . ' ' . $this->_param($name, $value), array(), $link);
    }

    /**
     * Same as compareColumn but instead of value another column is used.
     *
     * Example:
     *
     * [php]
     *  $condition->compareColumn('name', 'firstname'); // simple comparison with exact value of the columns.
     *  //   Result: "`tableName`.`name` = `tableName`.`firstName`"
     *  $condition->compareColumn('name', array('fname', 'lname')); // compare with multiple columns;
     *  //   Result: "`tableName`.`name` IN (`tableName`.`fname`, `tableName`.`lname`)"
     *  $condition->compareColumn('age', '>=fname'); // must be equal or bigger than selected column
     *  //    Result: "`tableName`.`age` >= `tableName`.`fname`"
     *  $condition->compareColumn('name', 'fname', true); // partial match
     *  //    Result: "`tableName`.`name` LIKE CONCAT('%', `tableName`.`fname`, '%')"
     * [/php]
     * @param string $column1
     * @param string $column2
     * @param boolean $partial
     * @param string $link
     * @return \mpf\datasources\sql\ModelCondition
     */
    public function compareColumns($column1, $column2, $partial = false, $link = 'AND') {
        $this->conditionColumns[] = $column2;
        $this->conditionColumns[] = $column1;
        if (is_array($column2)) {
            foreach ($column2 as &$col)
                $col = $this->_column($col);
            return $this->addCondition($this->_column($column1) . ' IN (' . implode(', ', $column2) . ')', array(), $link);
        }
        if ($partial) {
            return $this->addCondition($this->_column($column1) . ' LIKE CONCAT("%", ' . $this->_column($column2) . ' , "%")', array(), $link);
        }
        $operators = array('>=', '<=', '!=', '>', '<');
        $matched = false;
        foreach ($operators as $operator) {
            if ($operator == substr($column2, 0, strlen($operator))) {
                $column2 = trim(substr($column2, strlen($operator)));
                $matched = true;
                break;
            }
        }
        $operator = $matched ? $operator : '=';
        return $this->addCondition($this->_column($column1) . ' ' . $operator . ' ' . $this->_column($column2), array(), $link);
    }

    /**
     * This is a shortcut to compareColumn when only multiple values can be sent.
     * @param string $column Column name to be compared
     * @param string[] $values List of possible values
     * @param string $link Connection to other conditions can be "AND" or "OR"
     * @return \mpf\datasources\sql\ModelCondition
     */
    public function addInCondition($column, $values, $link = 'AND') {
        $this->conditionColumns[] = $column;
        return $this->compareColumn($column, $values, false, $link);
    }

    /**
     * Same as `addInCondition()` but it will use `"NOT IN"` condition.
     * @param string $column Column name to be compared
     * @param string[] $values List of values
     * @param string $link Connection to existent condition
     * @return \mpf\datasources\sql\ModelCondition
     */
    public function addNotInCondition($column, $values, $link = 'AND') {
        $this->conditionColumns[] = $column;
        $vals = array();
        foreach ($values as $val) {
            $vals[] = $this->_param($column, $val, true);
        }
        return $this->addCondition($this->_column($column) . ' NOT IN (' . implode(', ', $vals) .' )', array(), $link);
    }

    /**
     * Adds a new condition that compares value of a column be between to other values.
     * @param string $column Name of the column to be compared
     * @param int $start Minimum value
     * @param int $end Maximum value
     * @param string $link Connection to existent condition
     * @return \mpf\datasources\sql\ModelCondition
     */
    public function addBetweenCondition($column, $start, $end, $link = 'AND') {
        $this->conditionColumns[] = $column;
        return $this->addCondition($this->_column($column) . ' BETWEEN ' . $this->_param($column, $start) . ' AND ' . $this->_param($column, $end), array(), $link);
    }

    /**
     * Add a new condition to search a column in the result of a query; Will
     * create a subselect in the condition;
     * @param string $column
     * @param string $query
     * @param array $params
     * @param string $link
     * @return \mpf\datasources\sql\ModelCondition
     */
    public function addInSelectCondition($column, $query, $params = array(), $link = 'AND') {
        $this->conditionColumns[] = $column;
        return $this->addCondition($this->_column($column) . ' IN  (' . $query . ')', $params, $link);
    }

    /**
     * Checks if a column is null
     * @param string $column
     * @return ModelCondition
     * @throws \Exception
     */
    public function addIsNullCondition($column){
        $this->conditionColumns[] = $column;
        return $this->addCondition($this->_column($column) . " IS NULL");
    }

    /**
     * Checks if a column is not null
     * @param string $column
     * @return ModelCondition
     * @throws \Exception
     */
    public function addIsNotNullCondition($column){
        $this->conditionColumns[] = $column;
        return $this->addCondition($this->_column($column) . " IS NOT NULL");
    }

    /**
     * Adds another condition to the current one.
     *
     * @param string $condition Condition to be added. Can be a string or an associative array column=>value
     * @param array|string[string] $params Optional, list of parameters used be the string condition
     * @param string $link Connection with old condition, by default it's "AND"
     * @return $this
     */
    public function addCondition($condition, $params = array(), $link = 'AND') {
        if (is_array($condition)) {
            $condition = $this->_fromArray($condition);
        }
        if (!$this->condition) { // if condition it's not already set then just add this one
            $this->condition = $condition;
            $this->setParams($params);
            return $this;
        }
        $this->condition = '(' . $this->condition . ') ' . $link . ' (' . $condition . ')';
        $this->setParams($params);
        return $this;
    }

    /**
     * @var RelationsParser
     */
    protected $relationsParser;

    /**
     * Get join info query for current condition;
     * @param bool $forCount
     * @return string
     */
    public function getJoin($forCount = false) {
        if (!$this->with) {
            return $this->join;
        }
        if (!$this->relationsParser) {
            $this->relationsParser = RelationsParser::parse($this->model, $this, $this->conditionColumns);
        }
        return ($forCount?$this->relationsParser->getForCount():$this->relationsParser->getForMainSelect()) . ' ' . $this->join;
    }

    public function getJoinParams(){
        if (!$this->relationsParser)
            return [];
        return $this->relationsParser->getConditionParams();
    }

    /**
     * Return list of relations that were not selected in main query.
     * @param DbModel[] $models
     * @return array
     */
    public function getExtraRelations($models) {
        if (!$this->relationsParser){
            return [];
        }
        $relationsLeft = $this->relationsParser->getRelationsToBeSelectedSeparately();
        foreach ($relationsLeft as $path => $details){
            $this->relationsParser->getChildrenForModels($models, $path, $details, $this->fields);
        }

    }

    /**
     * Get fields for select; If model is set then the fields are processed so
     * that it will return the data in a way that can be used to generate the models.
     * @return string
     */
    public function getSelect() {
        if ($this->fields && $this->fields != '*' && (!(is_string($this->fields) && '*' == $this->fields[0] && 'END) AS `__parentRelationKey`' == substr($this->fields, -29)))) {
            if (is_string($this->fields)) {
                return $this->fields;
            }
            $columns = array();
            foreach ($this->fields as $field) { // @TODO: column generation here.
                $columns[] = trim($field);
            }
            return implode(', ', $columns);
        }
        if ((is_string($this->fields) && '*' == $this->fields[0] && 'END) AS `__parentRelationKey`' == substr($this->fields, -29))){
            return implode(', ', $this->getAllColumnsForSelect()) . substr($this->fields, 1);
        } else {
            return implode(', ', $this->getAllColumnsForSelect());
        }
    }

    /**
     * Get main condition or "1" if none it's set
     * @return string
     */
    public function getCondition() {
        return $this->condition ? $this->condition : '1';
    }

    /**
     * Get having condition
     * @return string
     */
    public function getHaving() {
        return $this->having;
    }

    /**
     * Get order for select or update
     * @return string
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * Return sql Group
     * @return string
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * Return SQL limit;
     * @return string
     */
    public function getLimit() {
        if (false !== strpos($this->limit, ','))
            return $this->limit; // both offset and limit are in the limit attribute;
        return $this->limit ? $this->limit . ' OFFSET ' . $this->offset : '';
    }

    /**
     * Get called procedure name;
     * @return string
     */
    public function getProcedure() {
        return $this->procedure;
    }

    /**
     * Get select INTO option
     * @return string
     */
    public function getInto() {
        return $this->into;
    }

    /**
     * Get ModelCondition or subclass from a condition that can be string or array
     * or another condition object, in wich case it will return that object;
     * @param mixed $condition The original condition
     * @return static
     */
    public static function getFrom($condition, $model) {
        if (is_a($condition, '\\mpf\datasources\\sql\\ModelCondition')) {
            $condition->model = $model;
            return $condition;
        }
        if (is_array($condition) && count($condition))
            $cond = new static($condition);
        else {
            $cond = new static;
            if ($condition)
                $cond->addCondition($condition);
        }
        $cond->model = $model;
        return $cond;
    }

    /**
     * Set a single parameter used for query.
     * @param string $name
     * @param string|int $value
     * @return $this
     */
    public function setParam($name, $value) {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Set multiple parameters used by the query
     * @param string [string] $params
     * @return $this
     */
    public function setParams($params) {
        foreach ($params as $name => $value)
            $this->setParam($name, $value);

        return $this;
    }

    /**
     * Used internally to set a param. It will generate param name searching first
     * if it already exists or not; In case it already exists it will try to add
     * a suffix to it.
     * Examples: :ag_name, :ag_name0, :ag_name1, :ag_name2...
     * @param string $column column name
     * @param string|int $value value for the parameter
     * @param boolean $isIn specifiy if parameter is for "IN" condition;
     * @return string param name
     */
    protected function _param($column, $value, $isIn = false) {
        $column = str_replace('.', '_', $column);
        $name = ':ag_' . ($isIn ? 'in_' : '') . $column;
        if (!isset($this->params[$name])) { // in case it wasn't already used then it's simple
            $this->params[$name] = $value;
            return $name;
        }
        $i = -1;
        do {
            $i++;
            if (isset($this->params[$name . $i]))
                continue;
            $this->params[$name . $i] = $value;
            return $name . $i;
        } while ($i < 10000);
    }

    /**
     * Get escaped column name from input.
     * If it's set for select then it will also set the alias depending on relation.
     * Examples:
     *    column: name
     *      for select:  `t`.`name` as `t_name`
     *      condition: `t`.`name`
     *    column: rel.name
     *      for select: `rel`.`name` as `rel_name`
     *      condition: `rel`.`name`
     *    column: rel.subrel.name
     *      for select:  `rel_subrel`.`name` as `rel_subrel_name`
     *      condition: `rel_subrel`.`name`
     * @param string $name Original name, can be a simple column or relation.column or relation.relation2.column
     * @param boolean $forSelect Specify if it is for select or just for conditions
     * @param boolean $mainQuery
     * @return string name of the column
     * @throws \Exception
     */
    protected function _column($name, $forSelect = false, $mainQuery = true) {
        if ('*' == substr($name, -1) && $forSelect) {
            if (false !== strpos($name, '.')) {
                $table = substr($name, 0, strlen($name) - 2);
            } else {
                $table = 't';
            }
            $currentModel = $this->model;
            $dbTableName = $currentModel::getTableName();
            if ('t' !== $table) {
                $currentTable = $table;
                do {
                    $rel = substr($currentTable, 0, strpos($currentTable, '.'));
                    $rels = $currentModel::getRelations();
                    if (!isset($rels[$rel]))
                        throw new \Exception('Invalid column ' . $name . ' !(relation not found)');
                    $rel = $rels[$rel];
                    if ($mainQuery && (!DbRelations::isSelectedTogether($rel[0]))) // if the select it's created for main query
                        return false; // return false if it must not be in the main query.
                    $currentModel = $rel[1];
                    $dbTableName = $currentModel::getTableName();
                    $currentTable = substr($currentTable, strpos($currentTable, '.') + 1);
                } while (false !== strpos($currentTable, '.'));
            }

            $columns = $currentModel::getDb()->getTableColumns($dbTableName);
            $cols = array();
            foreach ($columns as $col) {
                $name = $table . '.' . $col;
                $cols[] = '`' . str_replace('.', '`.`', $name) . '` as `' . str_replace('.', '_', $name) . '`';
            }
            return implode(', ', $cols);
        }
        $name = (false !== strpos($name, '.')) ? $name : 't.' . $name;
        $select = '';
        if ($forSelect) {
            $select = ' as `' . str_replace('.', '_', $name) . '`';
        }
        return '`' . str_replace('.', '`.`', $name) . '`' . $select;
    }

    /**
     * Transforms an array condition to string. Parameters are automatically
     * generated and set.
     * Example:
     *   condition: array('col1'=>'val1', 'col2' => 'val2')
     *   separator: AND
     *      result: '`t`.`col1` = :col1 and `t`.`col2` = :col2'
     *      params: :col1 = val1 ,  :col2 = val2
     * @param string [string] $colValues List of columns and values to be compared
     * @param string $separator Separator to be used between conditions
     * @return string String condition generated from that array
     */
    protected function _fromArray($colValues, $separator = 'AND') {
        $condition = array();
        foreach ($colValues as $column => $value) {
            $condition[] = $this->_column($column) . ' = ' . $this->_param($column, $value);
        }
        return implode(' ' . $separator . ' ', $condition);
    }

    /**
     * Checks if the selected string is a SQL expression or a simple column so that
     * it will know if must processit or not using _column() method.
     * @param string $column
     * @return boolean
     */
    protected function _columnIsExpression($column) {
        $charsUsedInExpressions = array('(', ')', ' ', '`', '/', '-', '+', '%', '!', '*', ',');
        return ($column !== str_replace($charsUsedInExpressions, '', $column));
    }

    /**
     * Return list of columns to select from current tables;
     * @return string[]
     * @throws \Exception
     */
    protected function getAllColumnsForSelect() {
        $with = is_array($this->with) ? $this->with : ($this->with ? explode(',', $this->with) : array());
        if (!count($with))
            return ['*'];
        $model = $this->model;
        $db = $model::getDb();
        $columns = $this->_columnsForModel($model::getTableName(), $db);
        foreach ($this->relationsParser->getListOfSelectedRelations() as $name=>$relation){
            $cols = $this->_columnsForRelation($relation, $db, $name);
            foreach ($cols as $column) {
                $columns[] = $column;
            }
        }
        return $columns;
    }

    protected function _columnsForModel($tableName, PDOConnection $db){
        $sqlColumns = $db->getTableColumns($tableName);
        $columns = [];
        foreach ($sqlColumns as $column){
            $columns[] = "`t`.`{$column['Field']}` as ___t___{$column['Field']}";
        }
        return $columns;
    }

    protected function _columnsForRelation(DbRelation $relation, PDOConnection $connection, $relationName) {
        $sqlColumns = $connection->getTableColumns($relation->getTableName());
        $columnAlias = str_replace('.', '_', str_replace('_', '__', $relationName));
        $columns = [];
        foreach ($sqlColumns as $column) {
            $columns[] = "`{$relation->getTableAlias()}`.`{$column['Field']}` as ___{$columnAlias}___{$column['Field']}";
        }
        return $columns;
    }

//put your code here
}
