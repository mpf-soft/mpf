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

namespace mpf\loggers;

class InlineWebLogger extends Logger {

    protected function init($config = array()) {
        echo <<<STYLE
<style>
    .log-message{
        display:block;
        float:left;
        width:100%;
        margin-left:0;
        margin-top:5px;
        margin-bottom:5px;
        line-height: 18px;
        text-align:center;
        border-radius: 3px;
        font-family:Arial;
        font-size:12px;
        font-weight:100;
    }
        
    .log-message span{
        display:none;
    }    
        
    .log-message b{
        font-weight: bold;
    }
        
    .log-error, .log-critical, .log-emergency{
        box-shadow:0px 0px 3px orangered;
        background:rgb(255,102,0);
        border:1px solid #fff;
        border-left:none;
        border-right:none;
        color:#fff;
    }
        
    .log-emergency{
        text-decoration: underline;
    }
        
    .log-notice, .log-warning, .log-alert{
        box-shadow:0px 0px 3px orangered;
        background:rgba(255,102,0,0.7);
        border:1px solid #fff;
        border-left:none;
        border-right:none;
        color:#fff;
    }
    
    .log-debug, .log-info{
        color:#fff;
        box-shadow:0px 0px 3px limegreen;
        background:limegreen;
        border:1px solid #fff;
        border-left:none;
        border-right:none;
    }
</style>
STYLE;
        parent::init($config);
    }

    public function getLogs() {
        return array();
    }

    public function log($level, $message, array $context = array()) {
        $details = array();
        $context['time'] = microtime(true);
        foreach ($context as $k => $v) {
            $details[] = $k . ' : ' . (is_string($v) ? nl2br($v) : print_r($v, true));
        }
        $details = implode('<br />', $details);
        echo "<div class=\"log-message log-$level\"><b>$level : $message</b><span><br />$details</span></div>";
        $baseScriptsURL = \mpf\web\AssetsPublisher::get()->publishFolder(dirname(\mpf\base\AutoLoader::getLastRegistered()->path('\mpf\__assets\scripts\jquery')) . DIRECTORY_SEPARATOR . 'jquery');
        echo \mpf\web\helpers\Html::get()->scriptFile($baseScriptsURL . 'jquery.min.js');
        echo \mpf\web\helpers\Html::get()->script('$(document).ready(function(){'
                . '$(".log-message").click(function(){if ($("span", this).is(":visible")) {$("span", this).hide();} else {$("span", this).show();} })'
                . '})');
    }

}
