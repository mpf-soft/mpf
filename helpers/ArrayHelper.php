<?php
/**
 * Created by PhpStorm.
 * User: Mirel Mitache
 * Date: 29.10.2014
 * Time: 21:00
 */

namespace mpf\helpers;


use mpf\base\Helper;
//
//
class ArrayHelper extends Helper {

    public function value($array, $keyMap, $default = '') {
        $map = explode('.', $keyMap);
        foreach ($map as $m) {
            if (isset($array[$m])) {
                $array = $array[$m];
            } else {
                return $default;
            }
        }
        return $array;
    }

    public function filterPrefix(&$array, $prefix, $remove = false) {
        if ($remove) {
            foreach ($array as $k => $a) {
                if (strpos($k, $prefix) === 0) {
                    unset($array[$k]);
                }
            }
            return $array;
        } else {
            $result = array();
            foreach ($array as $k => $a) {
                if (strpos($k, $prefix) === 0) {
                    $result[$k] = $a;
                }
            }
            return $result;
        }
    }

    public function transform($array, $rule) {
        if (!$array)
            return [];
        $result = [];
        if (is_array($rule)) {
            foreach ($rule as $k => $v) {
                if (!is_array($v)) {
                    foreach ($array as $a) {
                        $v1 = explode(',', $v);
                        if (count($v1) > 1) {
                            $res = [];
                            foreach ($v1 as $va) {
                                $res[trim($va)] = $a[trim($va)];
                            }
                            $result[$a[$k]] = $res;
                        } else {
                            $result[$a[$k]] = $a[$v1[0]];
                        }
                    }
                }
            }
        } elseif (is_string($rule)) {
            $result = [];
            $fields = explode(',', $rule);
            foreach ($array as $a) {
                if (count($fields) > 1) {
                    $r = [];
                    foreach ($fields as $f) {
                        $r[trim($f)] = $a[trim($f)];
                    }
                } else {
                    $r = $a[trim($fields[0])];
                }
                $result[] = $r;
            }
        }
        return $result;
    }

} 