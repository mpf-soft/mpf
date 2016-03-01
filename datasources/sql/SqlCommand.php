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

/**
 * Description of SqlCommand
 *
 * @author mirel
 */
class SqlCommand extends \mpf\base\LogAwareObject {

    /**
     * Name of the table used for query
     * @var string
     */
    public $table;

    /**
     *
     * @var \mpf\datasources\sql\PDOConnection
     */
    public $connection;

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
    public $with;

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
     * If set to true will be used for INSER & UPDATE queries;
     * @var boolean
     */
    public $ignore;

    /**
     * List of parameters linked to current condition;
     * @var string[string]
     */
    protected $params = array();

    /**
     * Used by next() method to record current position.
     * @var int
     */
    protected $currentPos = 0;

    /**
     * @param array $columns2values
     * @return $this
     */
    public function compare($columns2values = []) {
        foreach ($columns2values as $column => $value) {
            $c = "`" . str_replace('.', '`.`', $column) . "`";
            if (is_array($value)) {
                foreach ($value as $k => $val) {
                    $keys[':_compare_' . $k . '_' . str_replace('.', '_', $column)] = $val;
                    $this->andWhere("$c IN (" . implode(', ', array_keys($keys)) . ")", $keys);
                }
            } else {
                $key = ':_compare_' . str_replace('.', '_', $column);
                $this->andWhere("$c = $key", [$key => $value]);
            }
        }
        return $this;
    }

    /**
     *
     * @param string $condition
     * @param string[] $params
     * @return \mpf\datasources\sql\SqlCommand
     */
    public function where($condition, $params = []) {
        $this->condition = $condition;
        return $this->setParams($params);
    }

    /**
     * @param $condition
     * @param string[] $params
     * @return $this
     */
    public function andWhere($condition, $params = []) {
        $this->condition = $this->condition ? "({$this->condition}) AND ($condition)" : $condition;
        return $this->setParams($params);
    }

    /**
     * @param $condition
     * @param string[] $params
     * @return $this
     */
    public function orWhere($condition, $params = []) {
        $this->condition = $this->condition ? "({$this->condition}) OR ($condition)" : $condition;
        return $this->setParams($params);
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setParam($name, $value) {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Set multiple params
     * @param array $params
     * @return $this
     */
    public function setParams($params) {
        foreach ($params as $name => $value) {
            $this->setParam($name, $value);
        }
        return $this;
    }

    /**
     *
     * @param string $fields
     * @return \mpf\datasources\sql\SqlCommand
     */
    public function fields($fields) {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param string $order
     * @return $this
     */
    public function orderBy($order) {
        $this->order = $order;
        return $this;
    }

    /**
     * @param string $group
     * @return $this
     */
    public function groupBy($group) {
        $this->group = $group;
        return $this;
    }

    /**
     * @param string $having
     * @return $this
     */
    public function having($having) {
        $this->having = $having;
        return $this;
    }

    /**
     * @param int $index
     * @return $this
     */
    public function offset($index) {
        $this->offset = $index;
        return $this;
    }

    /**
     * @param int $number
     * @return $this
     */
    public function limit($number) {
        $this->limit = $number;
        return $this;
    }

    /**
     * @param string $joinString
     * @return $this
     */
    public function join($joinString) {
        $this->join = $joinString;
        return $this;
    }

    /**
     * @param $seconds
     * @return $this
     */
    public function cache($seconds) {
        return $this;
    }

    /**
     * Select into option.
     * @param string $into
     * @return $this
     */
    public function into($into) {
        $this->into = $into;
        return $this;
    }

    /**
     * Returns a single row as assoc array
     * @return string[string]
     */
    public function first() {
        $oldLimit = $this->limit;
        $this->limit = 1;
        $rows = $this->get();
        $this->limit = $oldLimit;
        return isset($rows[0]) ? $rows[0] : null;
    }

    /**
     * Return results one by one. If a limit is set it won't go further than the selected limit.
     * Also it will start from the selected offset if one is set.
     * @return null|string[string]
     */
    public function next() {
        if ($this->limit && $this->currentPos >= $this->limit) { // it won't go further than the selected limit
            return null;
        }
        $oldLimit = $this->limit;
        $oldOffset = $this->offset;
        $this->limit = 1;
        $this->offset += $this->currentPos;
        $rows = $this->get();
        $this->limit = $oldLimit;
        $this->offset = $oldOffset;
        $this->currentPos++;
        return isset($rows[0]) ? $rows[0] : null;
    }

    /**
     * Creates select query, executes it and returns the result as assoc array.
     * @return string
     */
    public function get() {
        $q = "SELECT " . $this->fields . " FROM `" . $this->table . "` " . $this->join;
        if ($this->condition) {
            $q .= " WHERE {$this->condition}";
        }
        if ($this->group) {
            $q .= " GROUP BY {$this->group}";
            if ($this->having) {
                $q .= " HAVING {$this->having}";
            }
        }
        if ($this->order) {
            $q .= " ORDER BY {$this->order}";
        }
        if ($this->limit) {
            $q .= " LIMIT {$this->limit}";
        }
        if ($this->offset) {
            $q .= " OFFSET {$this->offset}";
        }
        if ($this->procedure) {
            $q .= " PROCEDURE {$this->procedure}";
        }
        if ($this->into) {
            $q .= " INTO {$this->into}";
        }
        if ($this->for_update) {
            $q .= " FOR UPDATE";
        }
        if ($this->lock_in_share_mode) {
            $q .= " LOCK IN SHARE MODE";
        }
        return $this->connection->queryAssoc($q, $this->params);
    }

    /**
     * Return number of rows for current options.
     * @return int
     */
    public function count() {
        $oldFields = $this->fields;
        $this->fields = 'COUNT(*) as number';
        $number = $this->get();
        $number = $number[0]['number'];
        $this->fields = $oldFields;
        return $number;
    }

    /**
     * Updates a single column and increments it with selected value. Returns number of affected rows.
     * @param string $column
     * @param int $value
     * @return int
     */
    public function increment($column, $value) {
        $this->params[':column'] = $value;
        $query = "UPDATE `{$this->table}` {$this->join} SET `$column` = `$column` + :$column";
        if ($this->condition) {
            $query .= " WHERE {$this->condition}";
        }
        if ($this->limit) {
            $query .= " LIMIT {$this->limit}";
        }

        return $this->connection->execQuery($query, $this->params);
    }

    /**
     * Inserts a new row and returns the id or false if failed.
     * @param string[string] $columns
     * @param string[string]|string $duplicateKey Can have the value "ignore" or list of columns to update
     * @param string[] $params Optional params in case that string is sent to duplicate key
     * @return int|boolean
     */
    public function insert($columns, $duplicateKey = null, $params = []) {
        if (is_string($duplicateKey) && 'ignore' == strtolower($duplicateKey)) {
            $q = "INSERT IGNORE INTO {$this->table} ";
        } else {
            $q = "INSERT INTO {$this->table} ";
        }
        $cols = array();
        $vals = array();
        $this->params = $params;
        foreach ($columns as $name => $value) {
            $cols[] = '`' . $name . '`';
            $vals[] = ":$name";
            $this->params[":$name"] = $value;
        }
        $q .= "(" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
        if (is_array($duplicateKey) && count($duplicateKey)) {
            $q .= " ON DUPLICATE KEY UPDATE ";
            $updates = [];
            foreach ($duplicateKey as $column => $value) {
                $updates[] = "`$column` = :dp_{$column}";
                $this->params[':dp_' . $column] = $value;
            }
            $q .= implode(", ", $updates);
        } elseif ($duplicateKey && is_string($duplicateKey) && ('ignore' != strtolower($duplicateKey))){
            $q .= " ON DUPLICATE KEY UPDATE " . $duplicateKey;
        }
        if ($this->connection->execQuery($q, $this->params)) {
            return $this->connection->lastInsertId();
        } else {
            return false;
        }

    }

    /**
     * Update selected columns and return the number of affected rows.
     * @param $columns
     * @return int
     */
    public function update($columns) {
        $update = array();
        foreach ($columns as $k => $v) {
            $update[] = "`$k` = :$k";
            $this->params[":$k"] = $v;
        }
        $update = implode(", ", $update);
        $q = "UPDATE {$this->table} as `t` {$this->join} SET $update WHERE {$this->condition}";
        if ($this->limit) {
            $q .= " LIMIT {$this->limit}";
        }
        return $this->connection->execQuery($q, $this->params);
    }

    /**
     * Deletes rows that match condition. It will return number of affected rows.
     * @return int
     */
    public function delete() {
        $query = "DELETE FROM `{$this->table}` {$this->join}";
        if ($this->condition) {
            $query .= " WHERE {$this->condition}";
        }
        if ($this->limit) {
            $query .= " LIMIT {$this->limit}";
        }

        return $this->connection->execQuery($query, $this->params);
    }

}
