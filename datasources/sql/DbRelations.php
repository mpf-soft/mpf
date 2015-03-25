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

use mpf\base\App;
use mpf\WebApp;

/**
 * Description of DbRelations
 *
 * You can find the description for every relation below. Extra optiosn that can
 * be set for any relation:
 *  - limit
 *  - order
 *  - offset
 *  - required : if it's not found then the model won't be returned
 *
 * @author Mirel Mitache
 */
class DbRelations {

    /**
     * declaration model:
     * 'relName' => array(\mpf\datasources\sql\DbRelations::BELONGS_TO, '\app\models\SecondModelName',  'columnName', ..$options)
     * columnName represents the name of the column from this model that has the same value as primary key from SecondModelName
     * example:
     *  'user' => array(\mpf\datasources\sql\DbRelations::BELONGS_TO, '\app\models\User', 'user_id')
     */
    const BELONGS_TO = '1';

    /**
     * declaration model:
     *  'relName' => array(\mpf\datasources\sql\DbRelations::HAS_ONE, '\app\models\SecondModelName', 'columnName', ..$options)
     * columnName represents the name of the column from SecondModelNAme that has the same value as primary key from main model
     * example:
     *  'settings' => array(\mpf\datasources\sql\DbRelations::HAS_ONE, '\app\models\UserSettings', 'user_id')
     */
    const HAS_ONE = '2';

    /**
     * declaration model:
     *  'relName' => array(\mpf\datasources\sql\DbRelations::HAS_MANY, '\app\models\SecondModelName', 'columnName', ..$options)
     * columnName represents the name of the column from SecondModelName that has the same value as primary key from main model
     * example:
     *  'logs' => array(\mpf\datasources\sql\DbRelations::HAS_MANY, '\app\models\UserLogs', 'user_id')
     */
    const HAS_MANY = '3';

    /**
     * declaration model:
     *   'relName' => array(\mpf\datasources\sql\DbRelations::MANY_TO_MANY, '\app\models\SecondModelName', 'connectiontable(main_id, relation_id)', ..$options)
     *  connectiontable represents the name of the table that holds the connections between main and relation tables
     *  main_id column name that is the connection to main table
     *  relation_id column name that is the connection to relation table
     * example:
     *   'rights' => array(\mpf\datasources\sql\DbRelations::MANY_TO_MANY, '\app\models\Rights', 'users2rights(user_id, right_id)')
     */
    const MANY_TO_MANY = '4';

    public static function getJoinFromRelation($relationName, $relationDetails, $model) {
        switch ($relationDetails[0]) {
            case self::BELONGS_TO:
                return self::getBelongsTo($relationName, $relationDetails);
            case self::HAS_MANY:
                return self::getHasMany($relationName, $relationDetails, $model);
            case self::HAS_ONE:
                return self::getHasOne($relationName, $relationDetails, $model);
            case self::MANY_TO_MANY:
                return self::getManyToMany($relationName, $relationDetails, $model);
            default:
                throw new \Exception('Invalid relation type!');
        }
    }

    public static function getBelongsTo($relationName, $relationDetails) {
        $extModel = $relationDetails[1];
        $rn = explode('.', $relationName);
        unset($rn[count($rn) - 1]);
        $parentTable = count($rn) ? implode('_', $rn) : 't';
        $relationName = str_replace('.', '__', $relationName);
        $condition = "`$parentTable`." . $relationDetails[2] . " = `$relationName`.`" . WebApp::get()->sql()->getTablePk($extModel::getTableName()) . "`";
        return "LEFT JOIN `" . $extModel::getTableName() . "` as `$relationName` ON $condition";
    }

    public static function getHasMany($relationName, $relationDetails, $model) {
        $extModel = $relationDetails[1];
        $rn = explode('.', $relationName);
        unset($rn[count($rn) - 1]);
        $parentTable = count($rn) ? implode('_', $rn) : 't';
        $relationName = str_replace('.', '__', $relationName);
        $condition = "`$relationName`." . $relationDetails[2] . " = `$parentTable`.`" . WebApp::get()->sql()->getTablePk($model::getTableName()) . "`";
        return "LEFT JOIN `" . $extModel::getTableName() . "` as `$relationName` ON $condition";

    }

    public static function getHasOne($relationName, $relationDetails, $model) {
        $extModel = $relationDetails[1];
        $rn = explode('.', $relationName);
        unset($rn[count($rn) - 1]);
        $parentTable = count($rn) ? implode('_', $rn) : 't';
        $relationName = str_replace('.', '__', $relationName);
        $tableColumn = isset($relationDetails['tableColumn'])?$relationDetails['tableColumn']:WebApp::get()->sql()->getTablePk($model::getTableName());
        $condition = "`$relationName`." . $relationDetails[2] . " = `$parentTable`.`$tableColumn`";
        return "LEFT JOIN `" . $extModel::getTableName() . "` as `$relationName` ON $condition";
    }

    public static function getManyToMany($relationName, $relationDetails, $model) {
        $connection = explode('(', substr($relationDetails[2], 0, strlen($relationDetails[2]) - 1), 2); // remove ) from the end and separate by (
        $connectionTable = $connection[0];
        list($mainModelColumn, $relationModelColumn) = explode(',', $connection[1]);
        $mainModelColumn = trim($mainModelColumn);
        $relationModelColumn = trim($relationModelColumn);
        $relationPk = $relationDetails[1];
        $relationPk = $relationPk::getDb()->getTablePk($relationTable = $relationPk::getTableName());
        $mainModelPk = $model::getDb()->getTablePk($model::getTableName());
        $parentTableName = explode('.', $relationName);
        if (count($parentTableName) > 1) {
            unset($parentTableName[count($parentTableName) - 1]);
            $parentTableName = implode('_', $parentTableName);
        } else {
            $parentTableName = 't';
        }
        $relationName = str_replace('.', '__', $relationName);
        return "LEFT JOIN `$connectionTable` ON  `$parentTableName`.`$mainModelPk` = `$connectionTable`.`$mainModelColumn` " .
        "LEFT JOIN `$relationTable` as `$relationName` ON `$connectionTable`.`$relationModelColumn` = `$relationName`.`$relationPk`";
    }

    /**
     * Checks if that relation will be selected in the same query as the main
     * select or in a separate query.
     * @param string $relationType
     * @return bool
     */
    public static function isSelectedTogether($relationType) {
        return in_array($relationType, array(self::BELONGS_TO, self::HAS_ONE));
    }


    /**
     * @param DbModel[] $models
     * @param string $relationName
     * @param string[] $relationDetails
     * @return DbModel[]
     */
    public static function getRelations($models, $relationDetails) {
        $condition = new ModelCondition(array(
            'model' => $relationDetails[1]
        ));
        $usedKey = static::updateConditionForRelation($condition, $relationDetails, $models); // creates query and returns key;

        $keys = $results = array();
        $isSingleResultPerModel = in_array($relationDetails[0], array(self::BELONGS_TO, self::HAS_ONE));//only this two types have one result
        foreach ($models as $k => $model) {
            $keys[$model->$usedKey] = $k;
            $results[$k] = array();
        }
        return static::parseResults($keys, $results, $isSingleResultPerModel, get_class($models[0]), $condition, $relationDetails);
    }

    /**
     * @param ModelCondition $condition
     * @param string[] $relationDetails
     * @param DbModel[] $models
     * @return string
     */
    protected static function updateConditionForRelation(ModelCondition $condition, $relationDetails, $models) {
        if (isset($relationDetails[3]) && is_array($relationDetails[3])) {
            foreach ($relationDetails[3] as $k => $v) { // set extra options.
                $condition->$k = $v;
            }
        }

        switch ($relationDetails[0]) {
            case self::BELONGS_TO :
                return static::updateConditionForBelongsToRelation($condition, $relationDetails, $models);
            case self::HAS_ONE:
                return static::updateConditionForHasOneRelation($condition, $relationDetails, $models);
            case self::HAS_MANY:
                return static::updateConditionForHasManyRelation($condition, $relationDetails, $models);
            case self::MANY_TO_MANY:
                return static::updateConditionForManyToManyRelation($condition, $relationDetails, $models);
            default:
                break;
        }
    }

    /**
     * Updates $condition to add list of values from current table.
     * @param ModelCondition $condition
     * @param string[] $relationDetails
     * @param DbModel[] $models
     * @return string
     */
    protected static function updateConditionForBelongsToRelation(ModelCondition $condition, $relationDetails, $models) {
        $pk = $relationDetails[1];
        $pk = App::get()->sql()->getTablePk($pk::getTableName());
        $values = array();
        foreach ($models as $model) {
            /* @var $model DbModel */
            $values[] = $model->{$relationDetails[2]};
        }
        $condition->addInCondition($pk, $values);
        if (is_string($condition->fields)) {
            $condition->fields .= ", `$pk` as `__parentRelationKey`"; // read all columns + parent relation key
        } elseif (is_array($condition->fields)) {
            $condition->fields[$pk] = '__parentRelationKey';
        }
        return $relationDetails[2];
    }

    /**
     * Updates condition for Has One  relation. Compares selected table columm from relation to pk of main models.
     * @param ModelCondition $condition
     * @param string[] $relationDetails
     * @param DbModel[] $models
     * @return string
     */
    protected static function updateConditionForHasOneRelation(ModelCondition $condition, $relationDetails, $models) {
        $model = get_class($models[0]);
        $modelsColumn = $model::getDb()->getTablePk($model::getTableName());
        $values = array();
        foreach ($models as $model) {
            /* @var $model DbModel */
            $values[] = $model->$modelsColumn;
        }
        $condition->addInCondition($relationDetails[2], $values);
        if (is_string($condition->fields)) {
            $condition->fields .= ", `{$relationDetails[2]}` as `__parentRelationKey`"; // read all columns + parent relation key
        } elseif (is_array($condition->fields)) {
            $condition->fields[$relationDetails[2]] = '__parentRelationKey';
        }
        return $modelsColumn;
    }

    /**
     * Updates condition for Has Many relations. The condition is the same as for Has One relation, the only difference is
     * the limit to 1 that is not added here but in the caller method.
     * @param ModelCondition $condition
     * @param $relationDetails
     * @param $models
     * @return mixed
     */
    protected static function updateConditionForHasManyRelation(ModelCondition $condition, $relationDetails, $models) {
        return static::updateConditionForHasOneRelation($condition, $relationDetails, $models);
    }

    protected static function updateConditionForManyToManyRelation(ModelCondition $condition, $relationDetails, $models) {
        // details form:  connection_table(main_model_column, relation_model_column)
        $connection = explode('(', substr($relationDetails[2], 0, strlen($relationDetails[2]) - 1), 2); // remove ) from the end and separate by (
        $connectionTable = $connection[0];
        list($mainModelColumn, $relationModelColumn) = explode(',', $connection[1]);
        $mainModelColumn = trim($mainModelColumn);
        $relationModelColumn = trim($relationModelColumn);
        $relationPk = $condition->model;
        $relationPk = $relationPk::getDb()->getTablePk($relationTable = $relationPk::getTableName());
        $mainModelPk = get_class($models[0]);
        $mainModelPk = $mainModelPk::getDb()->getTablePk($mainModelPk::getTableName());
        //adding join with connection table.
        $condition->join .= "INNER JOIN `$connectionTable` ON `$connectionTable`.`$relationModelColumn` = `t`.`$relationPk`";
        $values = array();
        foreach ($models as $k => $model) {
            $values[] = $model->$mainModelPk;
        }
        $condition->addInCondition($connectionTable .'.'. $mainModelColumn, $values);
        if ('*' == $condition->fields) {
            $condition->fields = "`t`.*, `$connectionTable`.`$mainModelColumn` as `__parentRelationKey`";
        } elseif (is_string($condition->fields)) {
            $condition->fields .= ", `$connectionTable`.`$mainModelColumn` as `__parentRelationKey`";
        } elseif (is_array($condition->fields)) {
            $condition->fields["`$connectionTable`.`$mainModelColumn`"] = "__parentRelationKey";
        }
        return $mainModelPk;
    }

    /**
     * @param string[] $keys
     * @param array $results
     * @param bool $isSingleResultPerModel
     * @param string $modelClass
     * @param ModelCondition $condition
     * @param string[] $relationDetails
     * @return mixed
     * @throws \Exception
     */
    protected static function parseResults($keys, $results, $isSingleResultPerModel, $modelClass, $condition, $relationDetails) {
        /* @var $modelClass \mpf\datasources\sql\DbModel */
        $relationClass = $relationDetails[1];
        $allResults = $modelClass::getDb()->queryClass($condition, $relationDetails[1], $condition->getParams(), array(
            array(
                '_columns' => $modelClass::getDb()->getTableColumns($relationClass::getTableName()),
                '_pk' => $modelClass::getDb()->getTablePk($relationClass::getTableName()),
                '_isNewRecord' => false,
                '_action' => 'update',
                '_tableName' => $relationClass::getTableName(),
                '_db' => $modelClass::getDb()
            )
        ));
        foreach ($allResults as $result) {
            if ($isSingleResultPerModel) {
                $results[$keys[$result->__parentRelationKey()]] = $result;
            } else {
                $results[$keys[$result->__parentRelationKey()]][] = $result;
            }
        }
        return $results;
    }
}
