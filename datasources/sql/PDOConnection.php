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

use app\components\htmltools\Page;
use mpf\base\App;

class PDOConnection extends \PDO {

    use \mpf\base\LogAwareObjectTrait;

    private static $_instances;
    private $tableColumns = array();

    /**
     * Used to create a new connection
     * @var string
     */
    public $dns;

    /**
     * Used to create a new connection
     * @var string
     */
    public $username;

    /**
     * Used to create a new connection
     * @var string
     */
    public $password;

    /**
     * Extra PDO options.
     * @var string[]
     */
    public $driver_options = array();

    /**
     *
     * @param type $options
     */
    public function __construct($options = array()) {
        foreach (\mpf\base\Config::get()->forClass(get_called_class()) as $k => $n) {
            $this->$k = $n;
        }
        foreach ($options as $k => $n) {
            $this->$k = $n;
        }
        self::$_instances[md5(serialize($options))] = $this;
        $this->init($options);
        $this->driver_options[\PDO::ATTR_ERRMODE] = isset($this->driver_options[\PDO::ATTR_ERRMODE]) ? $this->driver_options[\PDO::ATTR_ERRMODE] : \PDO::ERRMODE_EXCEPTION;
        return parent::__construct($this->dns, $this->username, $this->password, $this->driver_options);
    }

    /**
     * @param array $options
     * @return PDOConnection
     */
    public static function get($options = array()) {
        if (!isset(self::$_instances[md5(serialize($options))]))
            return new PDOConnection($options);
        return self::$_instances[md5(serialize($options))];
    }

    /**
     * Select rows from table and return them as an associative array;
     * @param string $statement
     * @param array $params
     * @return array|null
     * @throws \PDOException
     */
    public function queryAssoc($statement, $params = array()) {
        $start = microtime(true);
        $query = $this->prepare($statement);
        try {
            $query->execute($params);
        } catch (\PDOException $e){
            $this->error($e->getMessage(), array(
                'File' => __FILE__,
                'Line' => __LINE__ - 4,
                'Query' => $statement .'',
                'Params' => $params,
                'Trace' => $e->getTraceAsString()
            ));
            return null;
        }
        $r = $query->fetchAll(\PDO::FETCH_ASSOC);
        $this->debug($statement . '', array(
            'params' => $params,
            'intoArray' => 'assoc',
            'rows' => count($r),
            'time' => microtime(true) - $start,
            'dns' => $this->dns
        ));
        return $r;
    }

    /**
     * Calls PDO query with PDO::FETCH_CLASS option;
     * @link http://ro1.php.net/pdo.query
     * @param string $statement
     * @param string $classname
     * @param array $params
     * @param array $ctorargs
     * @return array|null
     * @throws \PDOException
     */
    public function queryClass($statement, $classname, $params = array(), $ctorargs = array()) {
        $start = microtime(true);
        $q = $statement . '';
        if (is_a($statement, ModelCondition::className())){
            foreach ($statement->getJoinParams() as $k=>$v){
                $params[$k] = $v;
            }
        }
        $statement = $this->prepare($q);
        try {
            $statement->execute($params);
        } catch (\PDOException $e){
            $this->error($e->getMessage(), array(
                'File' => __FILE__,
                'Line' => __LINE__ - 4,
                'Query' => $q,
                'Params' => $params,
                'Trace' => $e->getTraceAsString()
            ));
            return null;
        }
        $objects = array();
        while ($ob = $statement->fetchObject($classname, $ctorargs)) {
            $objects[] = $ob;
        }
        $this->debug($q, array(
            'params' => $params,
            'intoClass' => $classname,
            'controllerArguments' => $ctorargs,
            'rows' => count($objects),
            'time' => microtime(true) - $start,
            'dns' => $this->dns
        ));
        return $objects;
    }

    /**
     * Executes a statement and returns the number of affected rows.
     * @param string $statement
     * @param array $params
     * @return int
     * @throws \PDOException
     */
    public function execQuery($statement, $params = array()) {
        $start = microtime(true);
        $query = $this->prepare($statement.'');
        try {
            $query->execute($params);
        } catch (\PDOException $e){
            $this->error($e->getMessage(), array(
                'File' => __FILE__,
                'Line' => __LINE__ - 4,
                'Query' => $statement .'',
                'Params' => $params,
                'Trace' => $e->getTraceAsString()
            ));die();
            return null;
        }
        $this->debug($statement, array(
            'params' => $params,
            'execute' => true,
            'rows' => $count = $query->rowCount(),
            'time' => microtime(true) - $start,
            'dns' => $this->dns
        ));
        return $count;
    }

    /**
     * Calls PDO query with PDO::FETCH_INTO object;
     * @link http://ro1.php.net/pdo.query
     * @param string $statement
     * @param object $object Instantiated object that will have attributes updated
     * @param array $params Statement parameters
     * @return object|array
     */
    public function queryInto($statement, $object, $params = array()) {
        $start = microtime(true);
        $query = $this->query($statement, \PDO::FETCH_INTO, $object);
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        $r = $query->fetchAll();
        $this->debug($statement . '', array(
            'params' => $params,
            'intoObject' => $object,
            'rows' => count($r),
            'time' => microtime(true) - $start,
            'dns' => $this->dns
        ));
        return $r;
    }

    /**
     * Get column used as primary key.
     * @param string $tableName
     * @return string|null
     */
    public function getTablePk($tableName) {
        $details = $this->getTableColumns($tableName);
        foreach ($details as $column) {
            if ($column['Key'] == 'PRI')
                return $column['Field'];
        }
        return null;
    }

    /**
     * Return a list of details about table columns;
     * @param string $tableName
     * @return array
     */
    public function getTableColumns($tableName) {
        if (!$this->tableColumns){
            if (App::get()->cacheExists('mpf:PDOConnection:tableColumns')){
                $this->tableColumns = App::get()->cacheValue('mpf:PDOConnection:tableColumns');
            }
        }
        if (!isset($this->tableColumns[$tableName])) {
            $this->tableColumns[$tableName] = $this->queryAssoc("SHOW COLUMNS FROM `$tableName`");
            App::get()->cacheSet('mpf:PDOConnection:tableColumns', $this->tableColumns);
        }
        return $this->tableColumns[$tableName];
    }

    /**
     * If column exists in table and it's enum or set it will return all available
     * options. If column exists but it's another type, it will return NULL.
     * If the column doesn't exist it will trow an Exception;
     * @param string $tableName
     * @param string $columnName
     * @return string[string]|null
     * @throws \Exception if column is not found
     */
    public function getColumnOptions($tableName, $columnName) {
        if (!in_array($this->getColumnType($tableName, $columnName), array('enum', 'set')))
            // only for enum or set columns the options are returned;
            return null;
        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            if ($column['Field'] != $columnName)
                continue;
            $options = explode('(', $column['Type'], 2); // get text after (
            $options = substr($options[1], 0, strlen($options[1]) - 1); // and remove ) from the end;
            $options = str_getcsv($options, ',', "'");
            $fOptions = [];
            foreach ($options as $opt){
                $fOptions[$opt] = $opt;
            }
            return $fOptions;
        }
        throw new \Exception("Column `$columnName` not found in table `$tableName`!");
    }

    /**
     * Returns column type.
     * @param string $tableName
     * @param string $columnName
     * @return string
     * @throws \Exception if column is not found
     */
    public function getColumnType($tableName, $columnName) {
        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            if ($column['Field'] != $columnName)
                continue;
            $type = explode('(', $column['Type'], 2);
            return $type[0];
        }
        throw new \Exception("Column `$columnName` not found in table `$tableName`!");
    }

    /**
     * Returns default value for selected column or NULL if there is no default;
     * @param string $tableName
     * @param string $columnName
     * @return string|int|null
     * @throws \Exception if column is not found
     */
    public function getColumnDefaultValue($tableName, $columnName) {
        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            if ($column['Field'] != $columnName)
                continue;
            return $column['Default'];
        }
        throw new \Exception("Column `$columnName` not found in table `$tableName`!");
    }

    /**
     * Return true or false depending if column can be null or not.
     * @param string $tableName
     * @param string $columnName
     * @return boolean
     * @throws \Exception if column is not found
     */
    public function getColumnCanBeNull($tableName, $columnName) {
        $columns = $this->getTableColumns($tableName);
        foreach ($columns as $column) {
            if ($column['Field'] != $columnName)
                continue;
            return ($column['Null'] == 'YES');
        }
        throw new \Exception("Column `$columnName` not found in table `$tableName`!");
    }

    /**
     * Returns a query builder for selected table
     * @param string $tableName
     * @return \mpf\datasources\sql\SqlCommand
     */
    public function table($tableName) {
        return new SqlCommand(array(
            'table' => $tableName,
            'connection' => $this
        ));
    }

    /**
     * Check if selected table exists.
     * @param $tableName
     * @return bool
     * @throws \Exception
     */
    public function tableExists($tableName) {
        $cache = [];
        if (App::get()->cacheExists('mpf:PDOConnection:tableList')){
            $cache = App::get()->cacheValue('mpf:PDOConnection:tableList');
            if (!is_array($cache)){
                $cache = []; // a fix for old wrong values;
            }
            if (isset($cache[$tableName])){
                return $cache[$tableName];
            }
        }
        $res = $this->queryAssoc("SHOW TABLES LIKE :table", array(':table' => $tableName));
        $cache[$tableName] = (bool)$res;
        App::get()->cacheSet('mpf:PDOConnection:tableList', $cache);
        return (bool)$res;
    }

}
