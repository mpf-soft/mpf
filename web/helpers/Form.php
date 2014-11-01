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

namespace mpf\web\helpers;

class Form extends \mpf\base\Helper {

    /**
     * Generates an input with the selected data. Value is automatically calculated
     * if it's sent as null. For this, it will check $_POST and $_GET for keys with
     * same name as input.
     * @param string $name
     * @param string $type
     * @param string|null $value
     * @param array $htmlOptions
     * @return string
     */
    public function input($name, $type = 'text', $value = null, $htmlOptions = array()) {
        // if value is null then it will check $_POST then $_GET for last filled value (in case of failed form submission)
        if (is_null($value)) {
            $value = $this->getArrayValue($_POST, $name);
            if (is_null($value)) {
                $value = $this->getArrayValue($_GET, $name);
            }
        }
        $r = "<input type='$type' name='$name' value='$value' ";
        foreach ($htmlOptions as $k => $v)
            $r .= "$k = '$v' ";
        return $r . ' />';
    }

    /**
     * Generates a hidden input field with the specified name, value and htmlOptions.
     * @param string $name
     * @param string|null $value
     * @param array $htmlOptions
     * @return string
     */
    public function hiddenInput($name, $value = null, $htmlOptions = array()) {
        return $this->input($name, 'hidden', $value, $htmlOptions);
    }

    /**
     * Returns an open form tag with the selected html options;
     * @param string [string] $htmlOptions
     * @return string
     */
    public function openForm($htmlOptions) {
        $r = '<form ';
        foreach ($htmlOptions as $k => $v)
            $r .= "$k = '$v' ";
        return $r . '>';
    }

    /**
     * Returns a close form tag;
     * @return string
     */
    public function closeForm() {
        return '</form>';
    }

    public function textarea($name, $value = null, $htmlOptions = null){
        $htmlOptions['name'] = $name;
        return Html::get()->tag('textarea', $value, $htmlOptions);
    }

    public function select($name, $options, $value = null, $htmlOptions = array(), $emptyValue = false) {
        $htmlOptions['name'] = $name;
        if (is_null($value)) {
            $value = $this->getArrayValue($_POST, $name);
            if (is_null($value)) {
                $value = $this->getArrayValue($_GET, $name);
            }
        }
        $opts = array();
        if (false !== $emptyValue) {
            if (!isset($value)) {
                $selected = 'selected="selected"';
            }
            $opts[] = "<option value='' $selected>$emptyValue</option>";
        }
        foreach ($options as $val => $label) {
            if (!isset($value)) {
                $selected = '';
            } else {
                $selected = (is_array($value) ? in_array($val, $value) : $value == $val) ? 'selected="selected"' : '';
                if (!$value && ($value !== $val) && is_int($val) && ('' === $value)) {
                    $selected = '';
                }
            }
            $opts[] = "<option value='$val' $selected>$label</option>";
        }
        $options = implode("\n", $opts);
        return Html::get()->tag('select', $options, $htmlOptions);
    }


    /**
     * Get value from array. It will parse the name and search for [ or ] to read the real value.
     * @param string [string] $source
     * @param string $name
     * @return null|string
     */
    protected function getArrayValue($source, $name) {
        if (false === strpos($name, '[')) {
            return isset($source[$name]) ? $source[$name] : null;
        }
        $name = explode('[', $name, 2);
        return isset($source[$name[0]]) ? $this->getArrayValue($source[$name[0]], substr($name[1], 0, strlen($name[1]) - 1)) : null;
    }

    /**
     * @param string $name
     * @param string $label
     * @param int $value
     * @param null|bool $checked
     * @param array $htmlOptions
     * @param string $template
     * @return mixed
     */
    public function checkbox($name, $label, $value = 1, $checked = null, $htmlOptions = array(), $template = '<input><label>') {
        if (null === $checked){
            $checked = $this->getArrayValue($_POST, $name);
            if (is_null($value)) {
                $checked = $this->getArrayValue($_GET, $name);
            }
        }
        if ($checked){
            $htmlOptions['checked'] = 'checked';
        }
        $htmlOptions['id'] = isset($htmlOptions['id'])?$htmlOptions['id']:str_replace(array('[', ']'), '_', $name) . '_'. $value;
        $input = $this->input($name, 'checkbox', $value,  $htmlOptions);
        $label = Html::get()->tag('label', $label, array('for' => $htmlOptions['id']));
        return str_replace(array('<input>', '<label>'), array($input, $label), $template);
    }

    /**
     * @param string $name
     * @param string $label
     * @param int $value
     * @param null|bool $checked
     * @param array $htmlOptions
     * @param string $template
     * @return mixed
     */
    public function radio($name, $label, $value = 1, $checked = null, $htmlOptions = array(), $template = '<input><label>') {
        if (null === $checked){
            $checked = $this->getArrayValue($_POST, $name);
            if (is_null($value)) {
                $checked = $this->getArrayValue($_GET, $name);
            }
        }
        if ($checked){
            $htmlOptions['checked'] = 'checked';
        }
        $htmlOptions['id'] = isset($htmlOptions['id'])?$htmlOptions['id']:str_replace(array('[', ']'), '_', $name) . '_'. $value;
        $input = $this->input($name, 'radio', $value,  $htmlOptions);
        $label = Html::get()->tag('label', $label, array('for' => $htmlOptions['id']));
        return str_replace(array('<input>', '<label>'), array($input, $label), $template);
    }

    /**
     * Generate a group of checkbox inputs
     * @param string $name
     * @param array $options
     * @param null $selected
     * @param array $htmlOptions
     * @param string $template
     * @param string $separator
     * @return string
     */
    public function checkboxGroup($name, $options, $selected = null, $htmlOptions, $template = '<input><label>', $separator = '<br />') {
        if (is_null($selected)) {
            $selected = $this->getArrayValue($_POST, $name);
            if (is_null($selected)) {
                $selected = $this->getArrayValue($_GET, $name);
            }
        }
        $r = array();
        foreach ($options as $value => $label) {
            $r[] = $this->checkbox($name.'[]', $label, $value, is_array($selected) ? in_array($value, $selected) : $selected == $value, $htmlOptions, $template);
        }
        return implode($separator, $r);
    }

    /**
     * @param string $name
     * @param array $options
     * @param null $selected
     * @param array $htmlOptions
     * @param string $template
     * @param string $separator
     */
    public function radioGroup($name, $options, $selected = null, $htmlOptions, $template = '<input><label>', $separator = '<br />') {
        if (is_null($selected)) {
            $selected = $this->getArrayValue($_POST, $name);
            if (is_null($selected)) {
                $selected = $this->getArrayValue($_GET, $name);
            }
        }
        $r = array();
        foreach ($options as $value => $label) {
            $r[] = $this->radio($name, $label, $value, $selected == $value, $htmlOptions, $template);
        }
        return implode($separator, $r);
    }

    /**
     * Get HTML input with jQuery UI date.
     * @param string $name
     * @param string $value
     * @param string $format
     * @param array $htmlOptions
     * @return string
     */
    public function date($name, $value = null, $format = 'yy-mm-dd', $htmlOptions = array()) {
        $return = Html::get()->mpfScriptFile('jquery.js') . Html::get()->mpfScriptFile('jquery-ui/jquery-ui.js');
        if (!isset($htmlOptions['id'])) {
            $htmlOptions['id'] = 'mdate-time' . str_replace(array('[', ']'), array('_', '__'), $name);
        }
        $s = Html::get()->script("$(function(){\$(\"#{$htmlOptions['id']}\").datepicker({dateFormat: '$format'});});");
        return $return . $this->input($name, 'text', $value, $htmlOptions) . $s;
    }

}
