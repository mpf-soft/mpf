<?php
/**
 * Created by PhpStorm.
 * User: Mirel Mitache
 * Date: 29.10.2014
 * Time: 21:07
 */

namespace mpf\datasources;


use mpf\base\LogAwareObject;

abstract class BaseModel extends LogAwareObject implements \ArrayAccess{

    /**
     * Saves updates for current model
     * @return mixed
     */
    abstract public function save();

    /**
     * If it's a new model then it will save it. If not, it will create a copy of the current model.
     * @return mixed
     */
    abstract public function saveAsNew();

    /**
     * Deletes current model.
     * @return mixed
     */
    abstract public function delete();

    /**
     * Updates model data from it's data source.
     * @return mixed
     */
    abstract public function reload();

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
        return property_exists($this, $offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset) {
        return $this->$offset;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value) {
        $this->$offset = $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset) {
        $this->$offset = null;
    }
}