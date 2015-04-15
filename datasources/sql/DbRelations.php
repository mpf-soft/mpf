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

}
