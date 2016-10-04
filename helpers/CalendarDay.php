<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 04.10.2016
 * Time: 11:26
 */

namespace mpf\helpers;


use mpf\base\LogAwareObject;

class CalendarDay extends LogAwareObject
{
    public $date, $time, $callBacks, $inCurrentMonth, $dayNumber;

    public function __get($name)
    {
        if (isset($this->callBacks[$name]))
            return call_user_func($this->callBacks[$name], $this);
        $this->error("Unknown attribute '$name'!");
    }

    /**
     * @return bool
     */
    public function isToday()
    {
        return date('Y-m-d') == $this->date;
    }
}