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
                $join[] = $this->relations[$name]->getSingular($this->modelClass, $name);
            }
        }
        return implode(" ", $join);
    }

    /**
     * @return string
     */
    public function getForMainSelect(){
        return "";
    }
}