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

namespace mpf\tools;

use mpf\base\TranslatableObject;

/**
 * Validator can be used to validate input data
 *    // Condition structure:
 *        key: field name
 *        value: array(
 *                -> type         : field type function to check if is true (function will have as parameters field value, an array with the rest of the conditions and validator object) and must return true or error message(wich can be string or array if there are multiple errors)
 *
 *                -> min         : used for string, text, list(how many can be selected), date, float and integer
 *                -> max         : used for string, text, list(how many can be selected), date, float and integer
 *
 *                -> min_words     : used for string and text
 *                -> max_words     : used for string and text
 *
 *                -> min_lines     : used for text
 *                -> max_lines     : used for text
 *
 *                -> expression     : used for regexp
 *
 *                -> values         : used for list and unique(for unique must not be found in this list to be valid)
 *
 *                -> null             : used for all  - if is not required and is not set then it will became null
 *                -> required         : used for all
 *                -> default_value : used for all (if not null and not set)
 *
 *                -> format         : used for date (php date format)
 *
 *                -> check_domain  : for email
 *
 *                -> add_http         : for url(if it doesn't start with: http, https, ftp, ftps)
 *                -> add_https     : for url(if it doesn't start with: http, https, ftp, ftps)
 *                -> add_ftp         : for url(if it doesn't start with: http, https, ftp, ftps)
 *                -> add_ftps         : for url(if it doesn't start with: http, https, ftp, ftps)
 *                -> online         : for url, will check if is online(via curl)
 *
 *
 *                -> display_title : the name of the field(in the form that is visible for user)
 *        )
 *
 * Example:
 *  // first initialize the validator
 *    $validator = new Validator(array('rules' => array(
 *              array('id', 'int', 'min'=>1),        // id must be integer
 *        array('first_name, last_name', 'string', 'min'=>3),    // first_name and last_name must be strings with minimum 3 characters
 *        array('age', 'int', 'min'=>10, 'max'=>105),            // age is integer between 10 and 105
 *        array('birth_date', 'date', 'format'=>'d/m/Y H:i', 'min'=>'1960-01-01'), // birth_date must be date, bigger that 1960-01-01, and it will also be formatted after the specified format
 *        array('id, first_name, last_name, age, birth_date', 'required', 'on'=>'insert'), // set fields as required on insert
 *        array('optional_info', 'string', 'null')
 *                                 ))); // optional info can be null. Also if is false or an empty string or array will be transformed in null
 *  // validate values
 *  $validator->validate(array('id'=>1, 'first_name'=>'Me', 'last_name'=>'Too', 'age'=>23, 'birth_date'=>'01/01/1960'));
 * @since 1.000
 * @author Mirel Mitache
 */
class Validator extends TranslatableObject {
    //put your code here

    /**
     * List of active rules.
     * @var string[]
     */
    public $rules = array();

    /**
     * List of fields labels to be used in error messages;
     * @var string[string]
     */
    public $labels = array();

    /**
     * List of condition aliases
     * @var array
     */
    public $aliases = array();

    /**
     * List of errors for each field transmited;
     * @var string[]
     */
    protected $errors = array();

    /**
     * Current values to be validated;
     * @var string[]
     */
    protected $values;

    /**
     * Get list of errors;
     * @return string[]
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Validate current row. Will return true if no errors or false if errors are found.
     * List of errors can be get from $this->getErrors();
     * @param string [string] $values
     * @param string|null $action
     * @return boolean true if there are no errors, false for errors.
     */
    public function validate(&$values, $action) {
        $this->errors = array();
        $this->values = $values;
        $valid = true;
        foreach ($this->rules as $rule) {
            $forActions = isset($rule['on']) ? explode(',', $rule['on']) : array();
            array_walk($forActions, function (&$item) {
                $item = trim($item);
            });
            if (isset($rule['on']) && (!in_array($action, $forActions))) {
                continue; // ignore rules set for other actions;
            }
            $valid = $this->__checkRule($rule) && $valid; // changed order so that it will check each rule even if the previous was already invalid
            // this was done in order to show errors for all fields at once
            // example of a problem: when registering if an invalid email was filled but no password it would
            // only show errors for password but it would not check email also so it would show no error there
            // until all the previous checks were already true.  It could mean a lot of tries to see and fix all
            // possible input errors from user part.
        }
        $values = $this->values;
        return $valid;
    }

    /**
     * Return current values as they are sent or updated by previous rules.
     * @return \string[]
     */
    public function getValues() {
        return $this->values;
    }

    /**
     * Get value of selected field or null if it doesn't exists
     * @param $field
     * @return null|mixed
     */
    public function getValue($field) {
        if (is_array($this->values)) {
            return isset($this->values[$field]) ? $this->values[$field] : null;
        }
        if (is_object($this->values)) {
            return $this->values->$field;
        }
        return null;
    }

    /**
     * Change value for a field.
     * @param string $field
     * @param mixed $value
     */
    public function setValue($field, $value) {
        if (is_array($this->values)) {
            $this->values[$field] = $value;
        } elseif (is_object($this->values)) {
            $this->values->$field = $value;
        }
    }

    /**
     * Validates fields using curent rule
     * @param string[] $rule
     * @return boolean
     */
    protected function __checkRule($rule) {
        $fields = explode(',', $rule[0]);
        if (is_string($rule[1])){
            $conditions = explode(',', $rule[1]);
            foreach ($conditions as $k=>$item){
                $item = trim($item);
                $conditions[$k] = isset($this->aliases[$item])?$this->aliases[$item]:$item;
            }
        } else {
            $conditions = array($rule[1]);
        }
        $valid = true;
        foreach ($conditions as $condition) {
            foreach ($fields as $field) {
                $field = trim($field);
                $label = isset($this->labels[$field]) ? $this->labels[$field] : ucwords(str_replace('_', ' ', $field));
                $errorMessage = isset($rule['message']) ? $this->translate(str_replace(array('__VALUE__', '__LABEL__'), array((!is_null($this->getValue($field))) ? $this->getValue($field) : ' - no value -', $label), $rule['message'])) : null;
                if ('required' == $condition && ((is_null($this->getValue($field))) || ('' == trim($this->getValue($field))))) {
                    if (!isset($this->errors[$field])) {
                        $this->errors[$field] = array();
                    }
                    $this->errors[$field][] = $errorMessage ? $errorMessage : $this->translate($label . ' is required!');
                    $valid = false; // if it's required but not defined then the rule it's not valid.
                    continue;
                } elseif (is_null($fieldValue = $this->getValue($field)) || (is_array($fieldValue) ? !count($fieldValue) : !trim($fieldValue))) { // if it's not required and not defined then there is nothing to check
                    continue;
                }
                if ('required' == $condition || 'safe' == $condition) { // skip required as it was checked above also skip safe as it is a model condition and not implemented here
                    continue;
                }
                try {
                    // actually check conditions here (except for required which is an exception from the rule
                    if (is_string($condition)) { // check local condition
                        $valid = $valid && $this->{'filter' . ucwords($condition)}($field, $rule, $label, $errorMessage);
                    } elseif (is_callable($condition)) { // check method condition
                        $valid = $valid && $condition($this, $field, $rule, $label, $errorMessage);
                    }
                } catch (\Exception $e) {
                    $valid = false;
                    if (!isset($this->errors[$field])) {
                        $this->errors[$field] = array();
                    }
                    $this->errors[$field][] = is_array($e) ? $e['message'] : $e->getMessage();
                }
            }
        }
        return $valid;
    }

    // ======================= FILTERS =============

    /**
     * Filters integer conditions. Extra options:
     *   - strict (can't be real values, just integers)
     *   - min (check for min value)
     *   - max (check for max value)
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string|null $message
     * @return boolean
     * @throws \Exception
     */
    protected function filterInt($field, $details, $label, $message) {
        if (!is_numeric($this->getValue($field))) {
            throw new \Exception($message ? $message : $this->translate("$label must be numeric!"));
        }

        if (isset($details['strinct']) && !is_int($this->getValue($field))) {
            throw new \Exception($message ? $message : $this->translate("$label must be integer!"));
        }

        if (isset($details['min']) && $this->getValue($field) < $details['min']) {
            throw new \Exception($message ? $message : $this->translate("$label must be bigger or equal than $details[min]!"));
        }

        if (isset($details['max']) && $this->getValue($field) > $details['max']) {
            throw new \Exception($message ? $message : $this->translate("$label must be smaller or equal than $details[min]!"));
        }
        return true;
    }

    /**
     * Accepted options:
     *  - trim
     *  - min (chars)
     *  - max (chars)
     *  - min_words
     *  - max_words
     *  - min_lines
     *  - max_lines
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string|null $message
     * @return boolean
     * @throws \Exception
     */
    protected function filterString($field, $details, $label, $message) {
        if (!(is_string($this->getValue($field)) || is_numeric($this->getValue($field)))) {
            throw new \Exception($message ? $message : $this->translate("$label must be string!"));
        }
        $this->setValue($field, $this->getValue($field) . '');
        if (isset($details['trim']) || in_array('trim', $details)) {
            $this->setValue($field, trim($this->getValue($field)));
        }
        if (isset($details['min']) && strlen($this->getValue($field)) < $details['min']) {
            throw new \Exception($message ? $message : $this->translate("$label must have at least $details[min] characters!"));
        }
        if (isset($details['max']) && strlen($this->getValue($field)) > $details['max']) {
            throw new \Exception($message ? $message : $this->translate("$label must have less than $details[min] characters!"));
        }
        $wordsCount = count(explode(' ', str_replace(array(',', ';', ':', '!', '?', '$', '%', '^', '/', '*', '(', ')', '[', ']', '@', '`', '~', '\'', '\\', '"', '}', '{', "\n"), ' ', $this->getValue($field))));
        if (isset($details['min_words']) && $wordsCount < $details['min_words']) {
            throw new \Exception($message ? $message : $this->translate("$label must have at least $details[min_words] words!"));
        }
        if (isset($details['max_words']) && $wordsCount > $details['max_words']) {
            throw new \Exception($message ? $message : $this->translate("$label must have less than $details[max_words] words!"));
        }
        $lines = count(explode("\n", $this->getValue($field)));
        if (isset($details['min_lines']) && $lines < $details['min_lines']) {
            throw new \Exception($message ? $message : $this->translate("$label must have at least $details[min_lines] lines!"));
        }
        if (isset($details['max_lines']) && $lines > $details['max_lines']) {
            throw new \Exception($message ? $message : $this->translate("$label must have less than $details[max_lines] lines!"));
        }
        return true;
    }

    /**
     * Checks if it's string, and then if it's alfanumeric. Same options as for
     * string check.
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string $message
     * @return boolean
     * @throws \Exception
     */
    protected function filterAlfanumeric($field, $details, $label, $message) {
        if (!$this->filterString($field, $details, $label, $message)) {
            return false;
        }
        if (ctype_alnum($this->getValue($field))) {
            return true;
        }

        throw new \Exception($message ? $message : $this->translate("$label must be alfanumeric!"));
    }

    /**
     * Checks if it's digit.
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string $message
     * @return boolean
     * @throws \Exception
     */
    protected function filterDigit($field, $details, $label, $message) {
        if (ctype_digit($this->getValue($field))) {
            return true;
        }
        throw new \Exception($message ? $message : $this->translate("$label must be digit!"));
    }

    /**
     * Checks if value/values are in list.
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string $message
     * @return boolean
     * @throws \Exception
     */
    protected function filterInlist($field, $details, $label, $message) {
        if ((!is_array($this->getValue($field))) && in_array($this->getValue($field), $details['values'])) {
            return true;
        } elseif (is_array($this->getValue($field))) {
            foreach ($this->getValue($field) as $val) {
                if (!in_array($val, $details['values'])) {
                    throw new \Exception($message ? $message : $this->translate("Invalid value $val!"));
                }
            }
            if (isset($details['min']) && count($this->getValue($field)) < $details['min']) {
                throw new \Exception($message ? $message : $this->translate("Must select at least $details[min] $label!"));
            }
            if (isset($details['max']) && count($this->getValue($field)) < $details['max']) {
                throw new \Exception($message ? $message : $this->translate("Must select less than $details[max] $label!"));
            }
        }
        throw new \Exception($message ? $message : $this->translate("Invalid value!"));
    }

    /**
     * Checks to see if matches the given expression
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string $message
     * @return boolean
     * @throws \Exception
     */
    protected function filterRegexp($field, $details, $label, $message) {
        if (preg_match($details['expression'], $this->getValue($field))) {
            return true;
        }
        throw new \Exception($message ? $message : $this->translate("Doesn't match expression!"));
    }

    /**
     * Checks if value is date or time and if it fits in interval
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string $message
     * @return boolean
     * @throws \Exception
     */
    protected function filterDate($field, $details, $label, $message) {
        if (false != ($date = @strtotime($this->getValue($field)))) {
            if (isset($details['min']) && $date < $details['min']) {
                throw new \Exception($message ? $message : $this->translate("Date must be bigger than $details[min]!"));
            }
            if (isset($details['max']) && $date > $details['max']) {
                throw new \Exception($message ? $message : $this->translate("Date must be smaller than $details[min]!"));
            }

            if (isset($details['format'])) {
                $this->setValue($field, date($details['format'], $date));
            }

            return true;
        }
        throw new \Exception($message ? $message : $this->translate("{$this->getValue($field)} is not a date!"));
    }

    /**
     *
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string $message
     * @return boolean
     * @throws \Exception
     */
    protected function filterUrl($field, $details, $label, $message) {
        if (!$this->filterString($field, $details, $label, $message)) {
            return false;
        }
        $urlregex = "^(https?|ftp)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?\$";
        if (preg_match('/' . $urlregex . '/i', $this->getValue($field))) {
            if (isset($details['online']) || in_array('online', $details)) {
                $valid = @fsockopen($this->getValue($field), 80, $errno, $errstr, 10);
                if ((!$valid))
                    throw new \Exception($message ? $message : $this->translate("Can't connect to URL!"));
            }
            return true;
        }
        throw new \Exception($message ? $message : $this->translate("Invalid URL!"));
    }

    /**
     * Check if field value is an email address
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string $message
     * @return bool
     * @throws \Exception
     */
    protected function filterEmail($field, $details, $label, $message) {
        if (!$this->filterString($field, $details, $label, $message)) {
            return false;
        }
        $email = $this->getValue($field);
        $isValid = true;
        $atIndex = strrpos($email, "@");

        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } else if (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } elseif (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } elseif (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
                // character not valid in local part unless 
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
                    $isValid = false;
                }
            }
            if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                // domain not found in DNS
                $isValid = false;
            }
        }
        if ($isValid) {
            return true;
        }
        throw new \Exception($message ? $message : $this->translate("This is not an email!"));
    }

    /**
     * Compare two fields or a field with a static value.
     * @param string $field
     * @param string [string] $details
     * @param string $label
     * @param string $message
     * @return bool
     * @throws \Exception
     */
    protected function filterCompare($field, $details, $label, $message) {
        if (isset($details['value'])) {
            $value = $details['value'];
            $secondLabel = '';
        } else {
            $value = $this->getValue($details['column']);
            $secondLabel = isset($this->labels[$details['column']]) ? $this->labels[$details['column']] : ucwords(str_replace('_', ' ', $details['column']));
        }

        if (!isset($details['sign']) || '==' == $details['sign']) {
            if ($this->getValue($field) == $value) {
                return true;
            }
            throw new \Exception($message ? $message : $this->translate(isset($details['value']) ? "Invalid value!" : "$label can't be different than $secondLabel!"));
        } else {
            $result = eval("return \$this->getValue(\$field) $details[sign] \$value");
            if ($result) {
                return true;
            }
            throw new \Exception($message ? $message : $this->translate("Invalid value!"));
        }
    }

}
