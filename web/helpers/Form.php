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


use mpf\WebApp;

class Form extends \mpf\base\Helper {

    /**
     * MarkItUp http://markitup.jaysalvat.com/ is used to generate HtmlTextareas by method ::htmlTextarea.
     * From this variable JS file for settings can be changed to a custom one.
     * @var null|string
     */
    public $tinyMCESkin = 'lightgray';

    public $tinyMCEOptionTemplates = [
        'basic' => [
            'plugins' => [
                "advlist autolink lists link image charmap print preview anchor",
                "searchreplace visualblocks code fullscreen",
                "insertdatetime media table contextmenu paste"
            ],
            'menubar' => false
        ],
        'advanced' => [
            'plugins' => [
                "advlist autolink lists link image charmap print preview anchor",
                "searchreplace visualblocks code fullscreen",
                "insertdatetime media table contextmenu paste"
            ],
            'toolbar' => "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
        ],
        'full' => [
            'theme' => "modern",
            'plugins' => [
                "advlist autolink lists link image charmap print preview hr anchor pagebreak",
                "searchreplace wordcount visualblocks visualchars code fullscreen",
                "insertdatetime media nonbreaking save table contextmenu directionality",
                "emoticons template paste textcolor colorpicker textpattern"
            ],
            'toolbar1' => "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
            'toolbar2' => "print preview media | forecolor backcolor emoticons",
            'image_advtab' => true
        ]
    ];

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

        return $r . '>' . ((isset($htmlOptions['method']) && 'post' == strtolower($htmlOptions['method']))?$this->hiddenInput(WebApp::get()->request()->getCsrfKey(), WebApp::get()->request()->getCsrfValue()):'');
    }

    /**
     * Returns a close form tag;
     * @return string
     */
    public function closeForm() {
        return '</form>';
    }

    /**
     * Generates an HTML Textarea using TinyMCE
     * @param $name
     * @param null $value
     * @param array $htmlOptions
     * @param string $tinyMCETemplate
     * @return string
     */
    public function htmlTextarea($name, $value = null, $htmlOptions = array(), $tinyMCETemplate = 'basic') {
        $r = Html::get()->mpfScriptFile('jquery.js') . Html::get()->mpfScriptFile('tinymce/tinymce.min.js') . Html::get()->mpfScriptFile('tinymce/jquery.tinymce.min.js');
        if (!isset($htmlOptions['id'])) {
            $htmlOptions['id'] = 'tinymce_' . uniqid();
        }
        $htmlOptions['class'] = (isset($htmlOptions['class']) ? $htmlOptions['class'] . ' ' : '') . 'input tinymce-textarea';
        $r .= $this->textarea($name, $value, $htmlOptions);
        $options = json_encode($this->tinyMCEOptionTemplates[$tinyMCETemplate]);
        $r .= Html::get()->script("$('#{$htmlOptions['id']}').tinymce($options)");
        return $r;
    }

    public function textarea($name, $value = null, $htmlOptions = array()) {
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
            $selected = "";
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
        if (null === $checked) {
            $checked = $this->getArrayValue($_POST, $name);
            if (is_null($value)) {
                $checked = $this->getArrayValue($_GET, $name);
            }
        }
        if ($checked) {
            $htmlOptions['checked'] = 'checked';
        }
        $htmlOptions['id'] = isset($htmlOptions['id']) ? $htmlOptions['id'] : str_replace(array('[', ']'), '_', $name) . '_' . $value;
        $input = $this->input($name, 'checkbox', $value, $htmlOptions);
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
        if (null === $checked) {
            $checked = $this->getArrayValue($_POST, $name);
            if (is_null($value)) {
                $checked = $this->getArrayValue($_GET, $name);
            }
        }
        if ($checked) {
            $htmlOptions['checked'] = 'checked';
        }
        $htmlOptions['id'] = isset($htmlOptions['id']) ? $htmlOptions['id'] : str_replace(array('[', ']'), '_', $name) . '_' . $value;
        $input = $this->input($name, 'radio', $value, $htmlOptions);
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
            $r[] = $this->checkbox($name . '[]', $label, $value, is_array($selected) ? in_array($value, $selected) : $selected == $value, $htmlOptions, $template);
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

    /**
     * Generates a submit button for current form.
     * @param string $value Value in this case will be used to display a text on the button. It will also be sent when form is submitted if a name is specified.
     * @param string $name Optional button name
     * @param array $htmlOptions Optional extra button html options
     * @return string
     */
    public function submitButton($value, $name='', $htmlOptions = []){
        return $this->input($name, 'submit', $value, $htmlOptions);
    }

    /**
     * Creates a button from a image. Button can be used to submit/cancel or anything else is added.
     * @param string $src Image URL
     * @param string $alt Text to be displayed in case that image is not loaded
     * @param string $name Optional a name can be added
     * @param string $value Optional a value can also be added
     * @param array $htmlOptions Extra HTML options. Values for scr, alt, type, name, value will be replaced with first parameters of this method
     * @return string
     */
    public function imageButton($src, $alt ='Submit', $name='', $value ='', $htmlOptions = []){
        $htmlOptions['src'] = $src;
        $htmlOptions['alt'] = $alt;
        return $this->input($name, 'image', $value, $htmlOptions);
    }

}
