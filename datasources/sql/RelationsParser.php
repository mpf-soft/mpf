<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 06.04.2015
 * Time: 12:12
 */

namespace mpf\datasources\sql;


use mpf\base\LogAwareObject;

class RelationsParser extends LogAwareObject {

    /**
     * @var DbRelation[]
     */
    public $relations;

    /**
     * @var DbModel|string
     */
    public $modelClass;

    /**
     * @var ModelCondition
     */
    public $condition;

    /**
     * @var string[]
     */
    public $conditionColumns;

    /**
     * @var array
     */
    protected $conditionParams = [];

    /**
     * It will keep here a list of names for relations that must be selected separately(from this level only).
     * @var array
     */
    protected $toBeSelectedSeparately = [];

    /**
     * List of relations that were selected with main model
     * @var DbRelation[]
     */
    protected $selectedWithMainModel = [];

    /**
     *
     * @param string $modelClass
     * @param ModelCondition $condition
     * @param string[]|string $conditionColumns
     * @return static
     */
    public static function parse($modelClass, ModelCondition $condition, $conditionColumns) {
        return new static(['relations' => $modelClass::getRelations(), 'modelClass' => $modelClass, 'condition' => $condition, 'conditionColumns' => $conditionColumns]);
    }

    public function init($config = []) {
        $modelClass = $this->modelClass;
        foreach ($this->relations as $name => $details) {
            if (is_array($details)) {
                $relationClass = $details[1];
                switch ($details[0]) {
                    case DbRelations::BELONGS_TO:
                        $this->relations[$name] = DbRelation::belongsTo($details[1], $details[2]);
                        break;
                    case DbRelations::HAS_ONE:
                        $this->relations[$name] = DbRelation::hasOne($details[1])->columnsEqual($modelClass::getDb()->getTablePk($modelClass::getTableName()), $details[2]);
                        break;
                    case DbRelations::HAS_MANY:
                        $this->relations[$name] = DbRelation::hasMany($details[1])->columnsEqual($modelClass::getDb()->getTablePk($modelClass::getTableName()), $details[2]);
                        break;
                    case DbRelations::MANY_TO_MANY:
                        //table_name(model_column, relation_column)
                        list($tableName, $columns) = explode("(", $details[2]);
                        $columns = explode(',', substr($columns, 0, strlen($columns) - 1));
                        $this->relations[$name] = DbRelation::manyToMany($details[1])->join([$tableName => $name . '_' . $tableName],
                            [$modelClass::getDb()->getTablePk($modelClass::getTableName()) => trim($columns[0])]
                        )->columnsEqual($name . '_' . $tableName . '.' . trim($columns[1]), $relationClass::getDb()->getTablePk($relationClass::getTableName()));
                        break;
                    default:
                        trigger_error("Invalid relation type {$details[0]}!");
                }
            }
            $this->relations[$name]->name = $name;
        }
        parent::init();
    }

    /**
     * Checks if condition depends on that relation or not. If it doesn't then there's no need for a join when counting.
     * @param $name
     * @return bool
     */
    protected function existsInCondition($name) {
        foreach ($this->conditionColumns as $column) {
            if (0 === strpos($column, $name . '.')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get list of condition params;
     * @return array
     */
    public function getConditionParams(){
        return $this->conditionParams;
    }

    public function getForCount() {
        $join = [];
        $this->conditionParams = [];
        foreach ($this->condition->with as $name) {
            if ($this->relations[$name]->isRequiredForCount() || $this->existsInCondition($name)) {
                $join[] = $this->relations[$name]->getWithParent($this->modelClass, $name);
                $params = $this->relations[$name]->getConditionParams();
                foreach ($params as $k=>$p){
                    $this->conditionParams[$k] = $p;
                }
            }
        }
        return implode(" ", $join);
    }

    /**
     * @return string
     */
    public function getForMainSelect() {
        $join = [];
        $this->conditionParams = [];
        $this->selectedWithMainModel = [];
        foreach ($this->condition->with as $name) {
            if ($this->hasSingleResult($name) || $this->existsInCondition($name)) {
                $this->selectedWithMainModel[$name] = $this->relations[$name];
                $join[] = $this->relations[$name]->getWithParent($this->modelClass, $name);
                $params = $this->relations[$name]->getConditionParams();
                foreach ($params as $k=>$p){
                    $this->conditionParams[$k] = $p;
                }
            }
        }
        return implode(" ", $join);
    }

    /**
     * List of relations that are selected with main model;
     * @return DbRelation[]
     */
    public function getListOfSelectedRelations() {
        return $this->selectedWithMainModel;
    }

    /**
     * @param $path
     * @param DbRelation $parentRelation
     * @return DbRelation
     */
    protected function initSubRelation($path, DbRelation $parentRelation) {
        $modelClass = $parentRelation->model;
        $relations = $modelClass::getRelations();
        $name = explode('.', $path);
        $name = $name[count($name) - 1];
        $relation = $relations[$name];
        if (is_array($relation)) {
            $details = $relation;
            $relationClass = $details[1];
            switch ($details[0]) {
                case DbRelations::BELONGS_TO:
                    $relation = DbRelation::belongsTo($details[1], $details[2]);
                    break;
                case DbRelations::HAS_ONE:
                    $relation = DbRelation::hasOne($details[1])->columnsEqual($modelClass::getDb()->getTablePk($modelClass::getTableName()), $details[2]);
                    break;
                case DbRelations::HAS_MANY:
                    $relation = DbRelation::hasMany($details[1])->columnsEqual($modelClass::getDb()->getTablePk($modelClass::getTableName()), $details[2]);
                    break;
                case DbRelations::MANY_TO_MANY:
                    //table_name(model_column, relation_column)
                    list($tableName, $columns) = explode("(", $details[2]);
                    $columns = explode(',', substr($columns, 0, strlen($columns) - 1));
                    $relation = DbRelation::manyToMany($details[1])->join([$tableName => $name . '_' . $tableName],
                        [$modelClass::getDb()->getTablePk($modelClass::getTableName()) => trim($columns[0])]
                    )->columnsEqual($name . '_' . $tableName . '.' . trim($columns[1]), $relationClass::getDb()->getTablePk($relationClass::getTableName()));
                    break;
                default:
                    trigger_error("Invalid relation type {$details[0]}!");
            }
        }
        return $relation;
    }

    /**
     * Checks if the relation returns a single result. It also checks for parents if it's a relation of a relation.
     * @param $name
     * @return bool
     */
    public function hasSingleResult($name) {
        $parts = explode(".", $name);
        $nameSoFar = "";
        foreach ($parts as $part) {
            $lastNameSoFar = $nameSoFar;
            $nameSoFar .= ($nameSoFar ? '.' : '') . $part;
            if (!isset($this->relations[$nameSoFar])) {
                $this->relations[$nameSoFar] = $this->initSubRelation($nameSoFar, $this->relations[$lastNameSoFar]);
            }
            if (!$this->relations[$nameSoFar]->hasSingleResult()) {
                if (!isset($this->toBeSelectedSeparately[$nameSoFar])) {
                    $this->toBeSelectedSeparately[$nameSoFar] = [
                        'relation' => $this->relations[$nameSoFar],
                        'subrelations' => []
                    ];
                }
                if ($nameSoFar != $name) {
                    $this->toBeSelectedSeparately[$nameSoFar]['subrelations'][] = substr($name, strlen($nameSoFar) + 1); // also keep name for separate select
                }
                return false;
            }
        }
        return true;
    }

    public function getRelationsToBeSelectedSeparately() {
        return $this->toBeSelectedSeparately;
    }

    /**
     * @param DbModel[] $models
     * @param string $relationPath
     * @param array $relationDetails
     * @param string|array $fields
     */
    public function getChildrenForModels($models, $relationPath, $relationDetails, $fields) {
        $fields = $this->getFieldsForRelationPath($fields, $relationPath);
        if (false === $fields) { // there is no need to read fields for this relations;
            return;
        }
        $subModelsPath = explode('.', $relationPath);
        unset($subModelsPath[count($subModelsPath) - 1]);
        if ($subModelsPath) {
            $subModels = $this->getAllChildsFromPath($models, implode('.', $subModelsPath));
        } else {
            $subModels = $models;
        }
        $condition = $relationDetails['relation']->getConditionForModels($subModels, $fields);
        /* @var $condition ModelCondition */
        if (count($relationDetails['subrelations'])) {
            $condition->with = $relationDetails['subrelations'];
        }
        $modelClass = $relationDetails['relation']->model;
        /* @var $modelClass DbModel */
        $children = $modelClass::findAll($condition);
        $singleResult = $relationDetails['relation']->hasSingleResult();
        $key = explode('.', $relationPath);
        $key = $key[count($key) - 1];
        $prepared = [];
        foreach ($children as $child) {
            if ($singleResult) {
                $prepared[$child->__parentRelationKey()] = $child;
            } else {
                $prepared[$child->__parentRelationKey()] = isset($prepared[$child->__parentRelationKey()]) ? $prepared[$child->__parentRelationKey()] : [];
                $prepared[$child->__parentRelationKey()][] = $child;
            }
        }
        foreach ($prepared as $k => $child) {
            $subModels[$k]->$key = $child;
        }
    }

    /**
     * @param DbModel $model
     * @param $relationName
     * @return DbModel[]|DbModel|null
     */
    public function getForSingleModel(DbModel $model, $relationName) {
        $condition = $this->relations[$relationName]->getConditionForModel($model);
        $relationClass = $this->relations[$relationName]->model;
        $children = $relationClass::findAll($condition);
        if ($this->relations[$relationName]->hasSingleResult()) {
            return isset($children[0]) ? $children[0] : null;
        } else {
            return $children;
        }
    }

    /**
     * Extracts only fields required for this relations
     * @param $fields
     * @param $relationPath
     * @return array|string
     */
    protected function getFieldsForRelationPath($fields, $relationPath) {
        if ('*' == $fields) {
            return '*';
        }

        if (is_array($fields)) { // only implemented for arrays for now
            $final = [];
            foreach ($fields as $column => $as) {
                $cName = is_numeric($column) ? $as : $column;
                if (($relationPath . '.') == substr($cName, 0, strlen($relationPath) + 1)) {
                    $cName = substr($cName, strlen($relationPath) + 1);
                    if (is_numeric($column)) {
                        $final[] = $cName;
                    } else {
                        $final[$cName] = $as;
                    }
                }
            }
            return $final;
        }

        return '*';
    }

    /**
     * Get list of all model childs from a selected path.
     * @param DbModel[] $models
     * @param string $relationPath
     * @return DbModel[]
     */
    protected function getAllChildsFromPath($models, $relationPath) {
        $path = explode('.', $relationPath, 2);
        $childs = [];
        $name = $path[0];
        foreach ($models as $model) {
            if (is_array($model->$name)) {
                $childs += $model->$name;
            } elseif ($model->$name) {
                $childs[] = $model->$name;
            }
        }
        if (!isset($path[1]) || !trim($path[1])) {
            return $childs;
        } else {
            return $this->getAllChildsFromPath($childs, $path[1]);
        }
    }
}