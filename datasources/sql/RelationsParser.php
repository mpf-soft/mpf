<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 06.04.2015
 * Time: 12:12
 */

namespace mpf\datasources\sql;


use mpf\base\LogAwareObject;

class RelationsParser extends LogAwareObject{

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
     * It will keep here a list of names for relations that must be selected separately(from this level only).
     * @var array
     */
    protected $toBeSelectedSeparately = [];

    /**
     *
     * @param string $modelClass
     * @param ModelCondition $condition
     * @param string[] $conditionColumns
     * @return static
     */
    public static function parse($modelClass, ModelCondition $condition, $conditionColumns){
        return new static(['relations' => $modelClass::getRelations(), 'modelClass' => $modelClass, 'condition' => $condition, 'conditionColumns' =>$conditionColumns]);
    }

    public function init($config = []){
        foreach ($this->relations as $name=>$details){
            if (is_array($details)){
                //init DbRelation from array;
            }
        }
        parent::init();
    }

    /**
     * Checks if condition depends on that relation or not. If it doesn't then there's no need for a join when counting.
     * @param $name
     * @return bool
     */
    protected function existsInCondition($name){
        foreach ($this->conditionColumns as $column){
            if (0 === strpos($column, $name.'.')){
                return true;
            }
        }
        return false;
    }

    public function getForCount(){
        $join = [];
        foreach ($this->condition->with as $name){
            if ($this->relations[$name]->isRequiredForCount() || $this->existsInCondition($name)){
                $join[] = $this->relations[$name]->getWithParent($this->modelClass, $name);
            }
        }
        return implode(" ", $join);
    }

    /**
     * @return string
     */
    public function getForMainSelect(){
        $join = [];
        foreach ($this->condition->with as $name){
            if ($this->hasSingleResult($name) || $this->existsInCondition($name)){
                $join[] = $this->relations[$name]->getWithParent($this->modelClass, $name);
            }
        }
        return implode(" ", $join);
    }

    public function getAsConditionForModels(){

    }

    /**
     * Checks if the relation returns a single result. It also checks for parents if it's a relation of a relation.
     * @param $name
     * @return bool
     */
    public function hasSingleResult($name){
        $parts  = explode(".", $name);
        $nameSoFar = "";
        foreach ($parts as $part){
            $nameSoFar = ($nameSoFar?'.':'').$part;
            if (!$this->relations[$name]->hasSingleResult()){
                $this->toBeSelectedSeparately[] = $name; // also keep name for separate select
                return false;
            }
        }
        return true;
    }

    public function getRelationsToBeSelectedSeparately(){
        return $this->toBeSelectedSeparately;
    }
}