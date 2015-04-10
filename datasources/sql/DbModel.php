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

use mpf\datasources\BaseModel;
use mpf\tools\Validator;

/**
 * Description of DbModel
 *
 * @author mirel
 */
abstract class DbModel extends BaseModel {

    private $_initiated = false;

    protected $_attributes = array();
    protected $_updatedAttributes = array();
    protected $_originalPk;

    /**
     * Used internally when selecting relations.
     * @var
     */
    protected $__parentRelationKey;

    /**
     * Current action. By default is insert ( when instantiating using new Model ), few other used actions are search and
     * update.
     * @var string
     */
    protected $_action = 'insert';

    /**
     * @var boolean
     */
    protected $_isNewRecord = true;

    /**
     * Name of the column used as primary key in the table.
     * @var string
     */
    protected $_pk;

    /**
     * List of table columns from db and info about each one.
     * @var string[]
     */
    protected $_columns;

    /**
     * Name of db table.
     * @var string
     */
    protected $_tableName;
    /**
     * Connection to database
     * @var PDOConnection
     */
    protected $_db;
    /**
     * List of instantiated relations.
     * @var ModelInterface[string]
     */
    protected $_relations;
    /**
     * List of relations that were searched and not found.
     * @var array
     */
    protected $_searchedRelations = array();
    /**
     * List of errors for current record
     * @var string[string]
     */
    protected $_errors;

    /**
     * @var \mpf\tools\Validator
     */
    protected $_validator;

    /**
     * List of attributes for each relation.
     * If there is an attribute for subrelation then it will be saved as relation.subrelation => attrs..
     * @var array
     */
    private $_attributesForRelations = array();

    /**
     * !! THIS METHOD IS CALLED AUTOMATICALLY BY "Validator" object !!
     *
     * Is used by Validator to check if a column is unique. In order to apply this filter for
     * one or more values use "unique" rule in rules list.
     * Optional parameters: 'column' or 'table'
     * @param Validator $validator
     * @param string $field
     * @param string[] $rule
     * @param string $label
     * @param string $message
     * @return bool
     * @throws \Exception
     */
    public function validateUnique(Validator $validator, $field, $rule, $label, $message) {
        $model = $validator->getValues();
        if (!is_a($model, __CLASS__)) {
            return true; // can only check models not arrays or any other object.
        }
        $column = isset($rule['column']) ? $rule['column'] : $field;
        $table = isset($rule['table']) ? $rule['table'] : $this->_tableName;
        /* @var $model DbModel */
        if ($model->isNewRecord()) { // if is new record then search in all
            if (!$this->_db->table($table)->where("`$column` = :$column")->setParam(':' . $column, $validator->getValue($field))->get()) {
                return true;
            }
        } else { // then search in all except current
            if (!$this->_db->table($table)->where("`$column` = :value AND `{$this->_pk}` != :pk")
                ->setParam(':value', $validator->getValue($field))
                ->setParam(':pk', (false != ($pk = $validator->getValue($this->_pk))) ? $pk : $this->_originalPk)->get()
            ) {
                return true;
            }
        }

        throw new \Exception($message ? $message : $validator->translate("$label must be unique!"));
    }

    /**
     * !! THIS METHOD IS CALLED AUTOMATICALLY BY "Validator" object !!
     *
     * Used for enum columns. Checks if the value is part of possible values from DB.
     * @param Validator $validator
     * @param $field
     * @param $rule
     * @param $label
     * @param $message
     * @return bool
     * @throws \Exception
     */
    public function validateEnum(Validator $validator, $field, $rule, $label, $message) {
        $table = isset($rule['table']) ? $rule['table'] : $this->_tableName;
        $column = isset($rule['column']) ? $rule['column'] : $field;

        $options = $this->_db->getColumnOptions($table, $column);
        if (in_array($validator->getValue($field), $options)) {
            return true; // all is good
        }
        throw new \Exception($message ? $message : $validator->translate("Invalid $label!"));
    }

    /**
     * !! THIS METHOD IS CALLED AUTOMATICALLY BY "Validator" object !!
     *
     * Checks if value exists in selected table for selected column.
     * @param Validator $validator
     * @param $field
     * @param $rule
     * @param $label
     * @param $message
     * @return bool
     * @throws \Exception
     */
    public function validateExternalKey(Validator $validator, $field, $rule, $label, $message) {
        foreach (array('table', 'column') as $option) {
            if (!isset($rule[$option])) {
                throw new \Exception("'externalKey' rule needs '$option' option in order to work! Example: array('types', 'externalKey', 'table'=> 'x', 'column' => 'y')");
            }
        }
        return true;
    }


    /**
     * @return \mpf\tools\Validator
     */
    public function getValidator() {
        if (!$this->_validator) {
            $rules = static::getRules();
            $model = $this;
            $aliases = array(
                'unique' => function (Validator $validator, $field, $rule, $label, $errorMessage) use ($model) {
                    return $model->validateUnique($validator, $field, $rule, $label, $errorMessage);
                },
                'enum' => function (Validator $validator, $field, $rule, $label, $errorMessage) use ($model) {
                    return $model->validateEnum($validator, $field, $rule, $label, $errorMessage);
                },
                'externalKey' => function (Validator $validator, $field, $rule, $label, $errorMessage) use ($model) {
                    return $model->validateExternalKey($validator, $field, $rule, $label, $errorMessage);
                }
            );
            $this->_validator = new Validator(array(
                'rules' => $rules,
                'labels' => static::getLabels(),
                'aliases' => $aliases
            ));
        }
        return $this->_validator;
    }

    /**
     * It will check if it's a new record and set required attributes. Also, if it's not a new record
     * will record originalPk to be later used for updates.
     * @param array $config
     * @return null
     */
    public function init($config = array()) {
        $this->_initiated = true;
        if ($this->isNewRecord()) {
            $this->_tableName = static::getTableName();
            $this->_db = $this->_db ? $this->_db : static::getDb();
            $this->_columns = static::getDb()->getTableColumns($this->_tableName);
            $this->_pk = static::getDb()->getTablePk($this->_tableName);
        } else {
            $this->_originalPk = $this->_attributes[$this->_pk];
            $this->afterLoad();
        }
        $this->applyRelationsAttributes();
        return parent::init($config);
    }

    /**
     * Reads all relations for current model.
     * @param $details
     * @return DbModel[]
     */
    private function getRelationFromDb($details) {
        $relations = DbRelations::getRelations(array($this), $details);
        return $relations[0];
    }

    private function applyRelationsAttributes() {
        foreach ($this->_attributesForRelations as $relation => $attributes) {
            $relation = explode('.', $relation);
            $model = $this;
            foreach ($relation as $current) {
                $relations = $model::getRelations();
                $class = $relations[$current][1];
                if (!$model->relationIsSet($current)) {
                    $model->$current = new $class(array(
                        '_pk' => static::getDb()->getTablePk($class::getTableName()),
                        '_isNewRecord' => false,
                        '_action' => 'update',
                        '_tableName' => $class::getTableName(),
                        '_db' => $class::getDb(),
                        '_attributes' => isset($this->_attributesForRelations[$current]) ? $this->_attributesForRelations[$current] : array()
                    ));
                }
                $model = $model->$current;
            }
        }
    }

    public function relationIsSet($relationName) {
        return isset($this->_relations[$relationName]);
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        if (array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        }
        foreach ($this->_columns as $column) { // check for columns that were not read yet [ useful for search and not only ]
            if ($column['Field'] == $name) {
                return ('search' == $this->_action) ? '' : $column['Default'];
            }
        }
        if (isset($this->_relations[$name])) {
            return $this->_relations[$name];
        }

        if (isset($this->_searchedRelations[$name])) {
            return null;
        }

        $relations = static::getRelations();
        if (isset($relations[$name])) {
            $this->_searchedRelations[$name] = true;
            return $this->_relations[$name] = $this->getRelationFromDb($relations[$name]);
        }

        trigger_error('Invalid attribute `' . $name . '`! A column or relation with that name was not found!');
    }

    /**
     * @param $name
     * @param $value
     * @throws \Exception
     */
    public function __set($name, $value) {
        $originalName = $name;
        if (!count($this->_columns)) { // had to be added here because of the order PDO assigns attributes when it creates a new object
            $this->_columns = static::getDb()->getTableColumns(static::getTableName());
        }
        $relation = null;
        if ('___' === substr($name, 0, 3)) {
            $table = explode('___', substr($name, 3), 2);
            if (count($table) >= 2) {
                $name = $table[1];
                $relation = str_replace('..', '_', str_replace('_', '.', $table[0]));
            }
        }
        if ($relation && 't' != $relation) { // if is a column from a relation
            if (!$this->_initiated) { // in case that is created from db result;
                if (!isset($this->_attributesForRelations[$relation])) {
                    $this->_attributesForRelations[$relation] = array();
                }
                $this->_attributesForRelations[$relation][$name] = $value;
            } else {
                $this->$relation->$name = $value; // if it is initiated then apply it.
            }
            return;
        }
        $isColumn = false;
        foreach ($this->_columns as $details) {
            if ($details['Field'] == $name) {
                $isColumn = true;
                break;
            }
        }
        if ($isColumn) {
            if (!array_key_exists($name, $this->_attributes) || $this->_attributes[$name] != $value) { // if no change was done there is no need for this.
                $this->_attributes[$name] = $value;
                if ($this->_initiated) { // to make sure that it wont' have all attributes on the updates. This way those that are set by
                    // PDO before __construct() is called will not be saved in this list
                    $this->_updatedAttributes[$name] = $value;
                }
            }
            return;
        }
        $class = get_class($this);
        $relations = $class::getRelations();
        if (isset($relations[$name])) {
            $this->_relations[$name] = $value;
            return;
        }
        echo "\nInvalid attribute `$originalName`! A column or relation with that name was not found!\n";
        trigger_error('Invalid attribute `' . $originalName . '`! A column or relation with that name was not found!');
    }

    /**
     * Save the updates or if it's a new record it will insert it.
     * @param bool $validate
     * @return bool
     */
    public function save($validate = true) {
        if ($validate) {
            if (!$this->validate()) {
                return false;
            }
        }
        if (!$this->beforeSave()) {
            return false;
        }

        if ($this->_isNewRecord) {
            $this->{$this->_pk} = $this->_db->table($this->_tableName)->insert($this->_updatedAttributes);
            $this->_originalPk =$this->{$this->_pk};
            $this->_isNewRecord = false; // so that the next save won't do another insert
            $this->reload();  // to get extra default values.
            if (!$this->{$this->_pk}) {
                return false; // there was an error when saving
            }
        }

        if (!$this->_updatedAttributes) {
            return true; // nothing to save.
        }

        $r = (bool)$this->_db->table($this->_tableName)
            ->where("`{$this->_pk}` = :__pk")->setParam(':__pk', $this->_originalPk)
            ->update($this->_updatedAttributes);
        $this->_originalPk = $this->{$this->_pk};
        if ($r) { // in case it saved the data then make it look like new.
            $this->_updatedAttributes = array();
        }
        return $r;
    }

    /**
     * Validate current data
     * @return bool
     */
    public function validate() {
        if (!$this->getValidator()->validate($this, $this->_action)) {
            $this->_errors = $this->getValidator()->getErrors();
            return false;
        }
        return true;
    }

    /**
     * It will create a copy of the current element in DB. This Object will be the new copy just inserted.
     * @param bool $validate
     * @return bool|int
     */
    public function saveAsNew($validate = true) {
        if ($validate && (!$this->validate())) {
            return false;
        }
        $originalBackup = $this->_originalPk;
        unset($this->_attributes[$this->_pk]);
        $this->_originalPk = $this->_db->table($this->_tableName)->insert($this->_attributes);
        if (!$this->_originalPk) {
            $this->_originalPk = $originalBackup;
            return false; // error when trying to insert;
        }
        $this->reload();
        return $this->_db->lastInsertId();
    }

    /**
     * Delete current row from table.
     * @return bool
     */
    public function delete() {
        if ($this->beforeDelete()) {
            if ((bool)$this->_db->table($this->_tableName)
                ->where("`{$this->_pk}` = :__pk")->setParam(':__pk', $this->_originalPk)
                ->delete()) {
                return $this->afterDelete();
            }
        }
    }

    /**
     * Reload info from DB for current object. Will also call afterLoad method in case updates are needed.
     */
    public function reload() {
        $this->_relations = array();
        $this->_attributes = $this->_db->table($this->_tableName)
            ->where("`{$this->_pk}` = :__pk")->setParam(':__pk', $this->_originalPk)
            ->first();
        $this->_updatedAttributes = array();
        $this->afterLoad();
    }

    /**
     * Add error messages for an attribute
     * @param string $attribute
     * @param string $errorMessage
     * @return $this
     */
    public function setError($attribute, $errorMessage) {
        if (!isset($this->_errors[$attribute])) {
            $this->_errors[$attribute] = array();
        }
        $this->_errors[$attribute][] = $errorMessage;
        return $this;
    }

    /**
     * Check if the entire model has errors or a specific attribute
     * @param null|string $attribute
     * @return boolean
     */
    public function hasErrors($attribute = null) {
        if (!count($this->_errors)) {
            return false;
        }
        if ($attribute && isset($this->_errors[$attribute]) && count($this->_errors[$attribute])) {
            return true;
        } elseif ($attribute) {
            return false;
        }
        return true;
    }

    /**
     * Get a list of errors for a specific attribute or for the entire model
     * @param null|string $attribute
     * @return string
     */
    public function getErrors($attribute = null) {
        return $attribute ? $this->_errors[$attribute] : $this->_errors;
    }

    /**
     * Change model action.
     * @param string $action
     * @return $this
     */
    public function setAction($action) {
        $this->_action = $action;
        return $this;
    }

    /**
     * Get current action.
     * @return string
     */
    public function getAction() {
        return $this->_action;
    }

    /**
     * Checks if current object is a new record or it's saved in DB
     * @return boolean
     */
    public function isNewRecord() {
        return $this->_isNewRecord;
    }

    public function afterLoad() {
        return true;
    }

    public function beforeSave() {
        return true;
    }

    public function beforeDelete(){
        return true;
    }

    public function afterDelete(){
        return true;
    }

    /**
     * Set attributes in a safe way. it will check rules for safe attributes and it will ignore those that are not safe.
     * To be used on search, insert, update from web form to make sure that the user doesn't add extra fields to the form.
     * @param string [string] $attributes
     * @return $this
     */
    public function setAttributes($attributes) {
        if (count($safe = static::getSafeAttributes($this->_action))) {
            foreach ($safe as $attribute) {
                if (isset($attributes[$attribute])) {
                    $this->$attribute = $attributes[$attribute];
                }
            }
            return $this;
        }
        foreach ($attributes as $name => $value) {
            $this->$name = $value;
        }
        return $this;
    }

    /**
     * Used internally when getting relations for model.
     * @return mixed
     */
    public function __parentRelationKey() {
        return $this->__parentRelationKey;
    }

    /**
     * Allows quick call for PK
     * @param $pk
     * @return static
     */
    public function __invoke($pk){
        return self::findByPk($pk);
    }

    /**
     * Get an instance of the model for selected action. Default is search.
     * @param string $action
     * @return static
     */
    public static function model($action = 'search') {
        return new static(array(
            '_action' => $action
        ));
    }

    /**
     * Return number of models for that specific condition
     * @param array|string|ModelCondition $condition
     * @param array $params
     * @return int
     */
    public static function count($condition, $params = array()) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition')) {
            $condition = ModelCondition::getFrom($condition, get_called_class());
        }
        /* @var $condition ModelCondition */
        $condition->fields = 'COUNT(*) as number';
        $condition->setParams($params);
        $number = static::getDb()->queryAssoc($condition->__toString(true), $condition->getParams());
        return $number[0]['number'];
    }

    /**
     * Return number of models for that specific condition
     * @param array $attributes
     * @param null|string|ModelCondition $condition
     * @param array $params
     * @return int
     */
    public static function countByAttributes($attributes, $condition = null, $params = array()) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition')) {
            $condition = ModelCondition::getFrom($condition, get_called_class());
        }
        /* @var $condition ModelCondition */
        foreach ($attributes as $column => $value) {
            $condition->compareColumn($column, $value);
        }
        return static::count($condition, $params);
    }

    /**
     * Delete all models that match condition.
     * @param string|ModelCondition $condition
     * @param array $params
     * @return int
     */
    public static function deleteAll($condition, $params = array()) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition')) {
            $condition = ModelCondition::getFrom($condition, get_called_class());
        }
        /* @var $condition ModelCondition */
        $condition->setParams($params);
        return static::getDb()->execQuery($condition->forDelete(), $condition->getParams());
    }

    /**
     * Delete all by attributes
     * @param array $attributes
     * @param null|string|ModelCondition $condition
     * @param array $params
     * @return int
     */
    public static function deleteAllByAttributes($attributes, $condition = null, $params = array()) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition')) {
            $condition = ModelCondition::getFrom($condition, get_called_class());
        }
        /* @var $condition ModelCondition */
        foreach ($attributes as $k => $v) {
            $condition->compareColumn($k, $v);
        }
        return static::deleteAll($condition, $params);
    }

    /**
     * Delete models by primary key
     * @param int|array $pk
     * @param null|string|ModelCondition $condition
     * @param array $params
     * @return int
     */
    public static function deleteByPk($pk, $condition = null, $params = array()) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition')) {
            $condition = ModelCondition::getFrom($condition, get_called_class());
        }
        /* @var $condition ModelCondition */
        $condition->compareColumn(static::getDb()->getTablePk(static::getTableName()), $pk);
        return static::deleteAll($condition, $params);
    }

    /**
     * Return connection to SQL Database
     * @return \mpf\datasources\sql\PDOConnection
     */
    public static function getDb() {
        return \mpf\base\App::get()->sql();
    }

    /**
     * Return all models that have the selected attributes.
     * @param string [string] $attributes
     * @param null|string|ModelCondition $condition
     * @param array $params
     * @return static[]
     */
    public static function findAllByAttributes($attributes, $condition = null, $params = array()) {
        $class = get_called_class();
        $condition = ModelCondition::getFrom($condition, $class);
        foreach ($attributes as $name=>$value) {
            $condition->compareColumn($name, $value);
        }
        return self::findAll($condition, $params);
    }

    /**
     * Return all models that have the selected primary keys.
     * @param int $pk
     * @param null|string|ModelCondition $condition
     * @param array $params
     * @return static[]
     */
    public static function findAllByPk($pk, $condition = null, $params = array()) {
        $class = get_called_class();
        $condition = ModelCondition::getFrom($condition, $class);
        $condition->compareColumn($class::getDb()->getTablePk($class::getTableName()), $pk);
        return self::findAll($condition, $params);
    }

    /**
     * Return a model searched by the selected attributes and optional condition;
     * @param array $attributes
     * @param null|string|ModelCondition $condition
     * @param array $params
     * @return null|static
     */
    public static function findByAttributes($attributes, $condition = null, $params = array()) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition'))
            $condition = ModelCondition::getFrom($condition, get_called_class());
        /* @var $condition \mpf\datasources\sql\ModelCondition */
        $oldLimit = $condition->limit;
        $condition->limit = 1;
        $models = static::findAllByAttributes($attributes, $condition, array());
        $condition->limit = $oldLimit;
        return isset($models[0]) ? $models[0] : null;
    }

    /**
     * Return single row model;
     * @param int $pk
     * @param mixed $condition
     * @return static
     */
    public static function findByPk($pk, $condition = null) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition'))
            $condition = ModelCondition::getFrom($condition, get_called_class());

        /* @var $condition \mpf\datasources\sql\ModelCondition */
        $oldLimit = $condition->limit;
        $condition->limit = 1;
        $models = static::findAllByPk($pk, $condition);
        $condition->limit = $oldLimit;
        return isset($models[0]) ? $models[0] : null;
    }

    /**
     * Return a single class for a single row;
     * @param mixed $condition
     * @param string[] $params
     * @return static
     */
    public static function find($condition, $params = array()) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition'))
            $condition = ModelCondition::getFrom($condition, get_called_class());

        /* @var $condition \mpf\datasources\sql\ModelCondition */
        $oldLimit = $condition->limit;
        $condition->limit = 1;
        $models = static::findAll($condition, $params);
        $condition->limit = $oldLimit;
        return isset($models[0]) ? $models[0] : null;
    }

    /**
     * Return all models that match the given condition.
     * @param string|ModelCondition $condition
     * @param array $params
     * @return static[]
     */
    public static function findAll($condition = '1=1', $params = array()) {
        /* @var $condition \mpf\datasources\sql\ModelCondition */
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition')) {
            $condition = ModelCondition::getFrom($condition, get_called_class());
        } else {
            $condition->model = $condition->model ? $condition->model : get_called_class();
        }
        $condition->setParams($params);
        $models = static::getDb()->queryClass($condition, get_called_class(), $condition->getParams(), array(
            array(
                '_columns' => static::getDb()->getTableColumns(static::getTableName()),
                '_pk' => static::getDb()->getTablePk(static::getTableName()),
                '_isNewRecord' => false,
                '_action' => 'update',
                '_tableName' => static::getTableName(),
                '_db' => static::getDb()
            )
        ));
        if (!$models){
            return [];
        }
        $extra = $condition->getExtraRelations();

        $joins = $condition->getParsedJoins();
        foreach ($joins as $name => $details) {
            self::selectRelation($name, $details, $joins, $models);
        }
        return $models;
    }

    private static function selectRelation($name, $details, $joins, $models) {
        if ($details['selected']) { // was already selected;
            return;
        }
        $name = explode('.', $name);
        if (count($name) > 1) {
            $parent = $name;
            unset($parent[count($parent) - 1]);
            $sparent = implode('.', $parent);
            if (!isset($joins[$sparent])) {
                trigger_error("Can't select relation " . implode('.', $name) . "! (parent not found in 'with' list)");
                return;
            }
            if (!$joins[$sparent]['selected']) { // if parent wasn't selected then select it now
                self::selectRelation($sparent, $joins[$sparent], $joins, $models);
            }

            foreach ($parent as $step) {
                $newModels = array();
                foreach ($models as $model) {
                    if (!$model->$step)
                        continue;
                    if (is_array($model->$step)) {
                        foreach ($model->$step as $m) {
                            $newModels[] = $m;
                        }
                    } else {
                        $newModels[] = $model->$step;
                    }
                }
                $models = $newModels;
            }
        }
        $name = $name[count($name) - 1];
        $allRelations = DbRelations::getRelations($models, $details['details']);

        foreach ($models as $k => $model) {
            $model->$name = is_array($allRelations[$k]) ? array_values($allRelations[$k]) : $allRelations[$k];
        }
    }

    /**
     * Rules examples:
     * array(
     *      array('id, name', 'safe'),
     *      array('oldPassword, newPassword, repeatedPassword', 'safe', 'on' => 'updatePassword')
     * )
     * @return array
     */
    public static function getRules() {
        return [];
    }

    /**
     * Relations example:
     * array(
     *      'users' => array(DbRelations::BELONGS_TO, '\\app\\models\\User', 'id_usr'), // type, modelClass, columnName
     *      'lastLog' => array(DbRelations::HAS_ONE, '\\app\\models\\Logs', 'id_obj', 'limit' => 1, 'order' => 'id DESC'), // type, modelClass, columnName
     * )
     * More details on "DbRelations" class description.
     * @return array
     */
    public static function getRelations() {
        return [];
    }

    /**
     * @return array
     */
    public static function getLabels() {
        return [];
    }

    /**
     * Get a list of safe attributes for current action or, if there are no rules then it will return
     * an empty list.
     * @param string $action
     * @return string[]
     */
    public static function getSafeAttributes($action = 'insert') {
        return self::getAttributesByRule($action, 'safe');
    }

    /**
     * Get a list of required attributes for selected action.
     * @param string $action
     * @return array
     */
    public static function getRequiredAttributes($action = 'insert'){
        return self::getAttributesByRule($action, 'require');
    }

    protected static function getAttributesByRule($action, $rule){
        if (!count(static::getRules())) {
            return array();
        }

        $rules = static::getRules();
        $attributes = array();
        foreach ($rules as $details) {
            if (is_callable($details[1])) {
                continue;
            }
            $conditions = explode(',', $details[1]);
            array_walk($conditions, function (&$item) {
                $item = trim($item);
            });
            $actions = isset($details['on']) ? explode(',', $details['on']) : array();
            array_walk($actions, function (&$item) {
                $item = trim($item);
            });
            if (in_array($rule, $conditions)) {
                if (!isset($details['on']) || in_array($action, $actions)) {
                    $list = explode(',', $details[0]);
                    foreach ($list as $attr) {
                        $attr = trim($attr);
                        if (!in_array($attr, $attributes)) {
                            $attributes[] = $attr;
                        }
                    }
                }
            }
        }
        return $attributes;
    }

    /**
     * Return number of affected rows;
     * @param $pk
     * @param $fields
     * @return int
     */
    public static function update($pk, $fields) {
        return static::getDb()->table(static::getTableName())
            ->where(static::getDb()->getTablePk(static::getTableName()) . ' = :pk')
            ->setParam(':pk', $pk)
            ->update($fields);
    }

    /**
     * @param $fields
     * @param $condition
     * @param array $params
     * @return int
     */
    public static function updateAll($fields, $condition, $params = array()) {
        if (!is_a($condition, '\\mpf\\datasources\\sql\\ModelCondition'))
            $condition = ModelCondition::getFrom($condition, get_called_class());
        /* @var $condition \mpf\datasources\sql\ModelCondition */
        $condition->setParams($params);
        return static::getDb()->table(static::getTableName())
            ->where($condition->getCondition())
            ->setParams($condition->getParams())
            ->update($fields);
    }

    /**
     * @param $fields
     * @param array $options
     * @return int
     */
    public static function insert($fields, $options = array()) {
        return static::getDb()->table(static::getTableName())
            ->insert($fields);
    }

}
