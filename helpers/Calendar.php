<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 04.10.2016
 * Time: 11:26
 */

namespace mpf\helpers;


use mpf\base\LogAwareObject;

class Calendar extends LogAwareObject
{
    public $month, $year, $firstDayString, $firstDayTime, $dayClass, $dayAttributesCallbacks;
    protected $_weeks;

    public static function get($month, $year, $dayAttributesCallbacks = [])
    {
        $firstDay = $year . '-' . (10 > $month ? '0' : '') . ltrim($month, '0') . '-01';
        $c = new Calendar(['month' => (int)$month, 'year' => $year, 'firstDayString' => $firstDay, 'firstDayTime' => strtotime($firstDay . ' 00:00:01'), 'dayAttributesCallbacks' => $dayAttributesCallbacks]);
        return $c;
    }

    /**
     * Get name of the current month
     * @return string
     */
    public function getMonthName()
    {
        return date('F', $this->firstDayTime);
    }

    protected function init($config)
    {
        if (!$this->dayClass) {
            $this->dayClass = CalendarDay::className();
        }
        $this->_calcWeeks();
        parent::init($config);
    }

    protected function _calcWeeks()
    {
        $weeks = [];
        $dayClass = $this->dayClass;
        $days = $this->getNumberOfDays();
        $d = substr($this->firstDayString, 0, 8);
        $cWeek = [1 => false, 2 => false, 3 => false, 4 => false, 5 => false, 6 => false, 7 => false];
        $firstWeekStartsWith = null;
        for ($i = 1; $i <= $days; $i++) {
            $cd = ($d . (10 > $i ? '0' : '') . $i);
            $dn = date('N', strtotime($cd));
            $cWeek[$dn] = new $dayClass(['date' => $cd, 'time' => strtotime($cd), 'callBacks' => $this->dayAttributesCallbacks, 'inCurrentMonth' => true, 'dayNumber' => $i]);
            $firstWeekStartsWith = is_null($firstWeekStartsWith) ? $dn : $firstWeekStartsWith;
            if ($dn == 7) {
                $weeks[] = $cWeek;
                $cWeek = [];
            }
        }
        if (count($cWeek))
            $weeks[] = $cWeek;
        $pMonthDays = $this->getPrevMonthDays();
        $d = date('Y-m-', $this->firstDayTime - 10000);
        for ($i = 1; $i < $firstWeekStartsWith; $i++) {
            $dn = $pMonthDays - ($firstWeekStartsWith - $i);
            $cd = ($d . (10 > $dn ? '0' : '') . $dn);
            $weeks[0][$i] = new $dayClass(['date' => $cd, 'time' => strtotime($cd), 'callBacks' => $this->dayAttributesCallbacks, 'inCurrentMonth' => false, 'dayNumber' => $dn]);
        }
        $lWeek = $weeks[count($weeks) - 1];
        $dn = 0;
        $d = date('Y-m-', strtotime('+1 month', $this->firstDayTime)) . '0';
        for ($i = 1; $i <= 7; $i++) {
            if (!isset($lWeek[$i])) {
                $dn++;
                $cd = $d . $dn;
                $lWeek[$i] = new $dayClass(['date' => $cd, 'time' => strtotime($cd), 'callBacks' => $this->dayAttributesCallbacks, 'inCurrentMonth' => false, 'dayNumber' => $dn]);
            }
        }
        $weeks[count($weeks) - 1] = $lWeek;
        $this->_weeks = $weeks;
    }


    /**
     * Week number starts with 1;
     * @param int $number
     * @return CalendarDay[]
     */
    public function getWeek($number)
    {
        return $this->_weeks[$number - 1];
    }

    /**
     * @return int
     */
    public function getNumberOfWeeks()
    {
        return count($this->_weeks);
    }

    /**
     * Get the number of days for this month
     * @return int
     */
    public function getNumberOfDays()
    {
        return date('t', $this->firstDayTime);
    }

    /**
     * Remove some seconds from first day time
     * @return bool|string
     */
    protected function getPrevMonthDays()
    {
        return date('t', $this->firstDayTime - 10000);
    }

    public function next()
    {
        if ($this->month < 12) {
            return self::get($this->month + 1, $this->year, $this->dayAttributesCallbacks);
        }
        return self::get(1, $this->year + 1, $this->dayAttributesCallbacks);
    }

    public function previous()
    {
        if ($this->month > 1) {
            return self::get($this->month - 1, $this->year, $this->dayAttributesCallbacks);
        }
        return self::get(12, $this->year - 1, $this->dayAttributesCallbacks);
    }

}