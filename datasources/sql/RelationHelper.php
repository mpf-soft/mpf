<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 27.11.2015
 * Time: 15:40
 */

namespace mpf\datasources\sql;


use mpf\base\LogAwareObject;

class RelationHelper extends LogAwareObject {

    /**
     * @var DbRelation
     */
    protected $relation;

    /**
     * @var string[]
     */
    protected $conditionParams = [];

    /**
     * @var string
     */
    protected $conditionForMainQuery;

    /**
     * @param DbRelation $relation
     * @return RelationHelper
     */
    public static function get(DbRelation $relation) {
        return new self(['relation' => $relation]);
    }

    /**
     * Get condition that will be added for the main query;
     * @return string
     */
    public function getConditionForMainQuery(){
        if (!is_null($this->conditionForMainQuery)){
            return $this->conditionForMainQuery;
        }
        $condition = [];
        foreach ($this->relation->joins as $join){

        }
    }

    /**
     * @return string[]
     */
    public function getParams(){
        return $this->conditionParams;
    }

    protected function processJoin($table, $columns, $values, $type, $models){

    }
}