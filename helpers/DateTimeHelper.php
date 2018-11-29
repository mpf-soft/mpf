<?php
/**
 * Created by PhpStorm.
 * User: Mirel Mitache
 * Date: 29.10.2014
 * Time: 21:50
 */

namespace mpf\helpers;


use mpf\base\Helper;

class DateTimeHelper extends Helper
{

    public $timeFormat = ' H:i';

    /**
     * @param string $date1
     * @param string $date2
     * @param string $unit Unit used for interval. Can be day, hour, minute or second
     * @return float
     */
    public function dateDiff($date1, $date2, $unit = 'day')
    {
        $intervals = ['day' => 3600 * 24, 'hour' => 3600, 'minute' => 60, 'second' => 1];
        if (!isset($intervals[$unit]))
            trigger_error("Invalid unit '$unit'. Valid values are: " . implode(', ', array_keys($intervals)) . ' !');
        return floor((strtotime($date1) - strtotime($date2)) / $intervals[$unit]);
    }

    public function niceDate($time = null, $noInterval = false, $showDayOfWeek = true, $noValueText = '-')
    {
        if (is_string($time) && (substr($time, 0, 10) === '0000-00-00'))
            return $noValueText;
        if (!$time)
            $time = time();
        if (is_string($time))
            $time = strtotime($time);
        $day = $this->translate(date('l', $time));
        $month = $this->translate(date('F', $time));
        if ($showDayOfWeek) {
            $niceDate = $day . ', ' . date('d', $time) . ' ' . $month . ' ' . date('Y', $time);
        } else {
            $niceDate = date('d', $time) . ' ' . $month . ' ' . date('Y', $time);
        }
        $niceDate .= date($this->timeFormat, $time);
        if ((date('Y-m-d') == date('Y-m-d', $time)) && (!$noInterval)) {
            if ((date('H') != date('H', $time)) && ((date('H') != (date('H', $time) + 1)) || (date('i') > date('i', $time)))) {
                $niceDate = date('H') - date('H', $time);
                if (1 === $niceDate) {
                    $niceDate = $this->translate('An hour ago');
                } elseif (-1 === $niceDate) {
                    $niceDate = $this->translate('One hour from now');
                } elseif ($niceDate < 0) {
                    $niceDate = str_replace('{x}', $niceDate * -1, $this->translate('{x} hours from now'));
                } else {
                    $niceDate = str_replace('{x}', $niceDate, $this->translate('{x} hours ago'));
                }
            } elseif (date('i') == date('i', $time)) {
                $niceDate = date('s') - date('s', $time);
                if ($niceDate < 2) {
                    $niceDate = 2;
                }
                $niceDate = str_replace('{x}', $niceDate, $this->translate('{x} seconds ago'));
            } else {
                if (date('H') == date('H', $time)) {
                    $niceDate = date('i') - date('i', $time);
                } else {
                    $niceDate = date('i') + 60 - date('i', $time);
                }
                if (1 === $niceDate) {
                    $niceDate = $this->translate('A minute ago');
                } elseif (-1 === $niceDate) {
                    $niceDate = $this->translate('One minute from now');
                } elseif ($niceDate < 0) {
                    $niceDate = str_replace('{x}', $niceDate * -1, $this->translate('{x} minute from now'));
                } else {
                    $niceDate = str_replace('{x}', $niceDate, $this->translate('{x} minutes ago'));
                }
            }
        }
        return $niceDate;
    }
} 