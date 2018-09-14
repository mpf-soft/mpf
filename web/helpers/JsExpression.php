<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 01.02.2016
 * Time: 13:37
 */

namespace mpf\web\helpers;


use mpf\base\MPFObject;

class JsExpression extends MPFObject {
    /**
     * @var string
     */
    public $expression;

    public static function get($expression) {
        return new self(['expression' => $expression]);
    }

    public function __toString() {
        return $this->expression;
    }
}