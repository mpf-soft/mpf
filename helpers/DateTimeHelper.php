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
    public $days = array('', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    public $months = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

    public function niceDate($date = null, $noInterval = false, $showDayOfWeek = true, $noValueText = '-')
    {
        if (is_string($date) && (substr($date, 0, 10) === '0000-00-00'))
            return $noValueText;
        if (!$date)
            $date = time();
        if (is_string($date))
            $date = strtotime($date);
        $day = $this->translate($this->days[(int)date('N', $date)]);
        $month = $this->translate($this->months[(int)date('m', $date)]);
        if ($showDayOfWeek) {
            $niceDate = $day . ', ' . date('d', $date) . ' ' . $month . ' ' . date('Y', $date);
        } else {
            $niceDate = date('d', $date) . ' ' . $month . ' ' . date('Y', $date);
        }
        if ((date('Y-m-d') == date('Y-m-d', $date)) && (!$noInterval)) {
            if ((date('H') != date('H', $date)) && ((date('H') != (date('H', $date) + 1)) || (date('i') > date('i', $date)))) {
                $niceDate = date('H') - date('H', $date);
                if (1 === $niceDate) {
                    $niceDate = $this->translate('An hour ago');
                } elseif (-1 === $niceDate) {
                    $niceDate = $this->translate('One hour from now');
                } elseif ($niceDate < 0) {
                    $niceDate = str_replace('{x}', $niceDate*-1, $this->translate('{x} hours from now'));
                } else {
                    $niceDate = str_replace('{x}', $niceDate, $this->translate('{x} hours ago'));
                }
            } elseif (date('i') == date('i', $date)) {
                $niceDate = date('s') - date('s', $date);
                if ($niceDate < 2) {
                    $niceDate = 2;
                }
                $niceDate = str_replace('{x}', $niceDate, $this->translate('{x} seconds ago'));
            } else {
                if (date('H') == date('H', $date)) {
                    $niceDate = date('i') - date('i', $date);
                } else {
                    $niceDate = date('i') + 60 - date('i', $date);
                }
                if (1 === $niceDate) {
                    $niceDate = $this->translate('A minute ago');
                } elseif (-1 === $niceDate) {
                    $niceDate = $this->translate('One minute from now');
                } elseif ($niceDate < 0) {
                    $niceDate = str_replace('{x}', $niceDate*-1, $this->translate('{x} minute from now'));
                } else {
                    $niceDate = str_replace('{x}', $niceDate, $this->translate('{x} minutes ago'));
                }
            }
        }
        return $niceDate;
    }
} 