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

namespace mpf\base;

use mpf\interfaces\LogAwareObjectInterface;

/**
 * A class that extends the basic {@class:\mpf\base\Object} and implements {@class:\mpf\interfaces\LogAwareObjectInterface}.
 *
 * It offers access to a series of methods that allows a developer to log any kind of information.
 *
 * To configure a logger you can add the following code in the config file:
 * [php] [
 * //...a simple logger that just displays error message in-line
 * "mpf\\interfaces\\LogAwareObjectInterface" => [
 *   "loggers" => [
 *     "mpf\\loggers\\InlineWebLogger"
 *   ]
 * ]
 * //...
 * ]
 * //...a better logger to be used on development server:
 * "mpf\\interfaces\\LogAwareObjectInterface" => [
 *   "loggers" => [
 *     "mpf\\loggers\\DevLogger"
 *   ]
 * ]
 * [/php]
 *
 * It is better to set the config for the interface instead of the this class because there are some classes that can't extend
 * {@class:\mpf\base\LogAwareObject} and are forced to just implement {@class:\mpf\interfaces\LogAwareObjectInterface} and use
 * {@class:\mpf\base\LogAwareObjectTrait} for the methods. That is the reason why this class has no methods and instead it uses
 * the trait to get all it's methods here. It's used more like a shortcut so that the developer won't have to implement the interface,
 * extend the {@class:\mpf\base\Object} and use the trait for each class that needs this.
 *
 */
class LogAwareObject extends Object implements LogAwareObjectInterface {

    use LogAwareObjectTrait;
}
