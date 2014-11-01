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

interface ModelInterface extends \mpf\interfaces\LogAwareObjectInterface {

    /**
     * @param string $action
     * @return static
     */
    public static function model($action = 'search');

    public static function find($condition, $params = array());

    public static function findByPk($pk, $condition = null);

    public static function findByAttributes($attributes, $condition = null);

    public static function findAll($condition = '1=1', $params = array());

    public static function findAllByPk($pk, $condition = null);

    public static function findAllByAttributes($attributes, $condition = null, $params = array());

    public static function count($condition, $params = array());

    public static function countByAttributes($attributes, $condition = null, $params = array());

    public static function update($fields, $pk);

    public static function updateAll($fields, $condition, $params = array());

    public static function deleteByPk($pk, $condition = null, $params = array());

    public static function deleteAll($condition, $params = array());

    public static function deleteAllByAttributes($attributes, $condition = null, $params = array());

    public static function insert($fields, $options = array());

    public static function getTableName();

    public static function getSafeAttributes($action = 'insert');

    public static function getRules();

    /**
     * @return \mpf\datasources\sql\ConnectionInterface
     */
    public static function getDb();

    public static function getRelations();
    
    public static function getLabels();

    public function save($validate = true);

    public function saveAsNew($validate = true);

    public function delete();

    public function reload();

    public function __get($name);

    public function __set($name, $value);

    public function isNewRecord();

    /**
     * @param $attributes
     * @return $this
     */
    public function setAttributes($attributes);

    /**
     * @param $action
     * @return $this
     */
    public function setAction($action);

    /**
     * @return string
     */
    public function getAction();
}
