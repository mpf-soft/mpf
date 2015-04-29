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

use mpf\web\AssetsPublisher;
use mpf\WebApp;

class Html extends \mpf\base\Helper {
    /**
     * Used by methods that require jQueryUI so that they will all load the same theme
     * @var string
     */
    public $jqueryUITheme = 'excite-bike';

    /**
     * @var array
     */
    private static $cssScriptHistory = array();

    /**
     * Adds style tags to a css text.
     * @param string $text css text
     * @param string $media
     * @return string
     */
    public function css($text, $media = '') {
        if ($media !== '')
            $media = ' media="' . $media . '"';
        return "<style type=\"text/css\"{$media}>\n/*<![CDATA[*/\n{$text}\n/*]]>*/\n</style>";
    }

    /**
     * Register inline script. You can choose to insert a script only once and then if the same script is detected then it won't be inserted again.
     * @param mixed $script
     * @return string
     */
    public function script($script) {
        $md5 = md5($script);
        if (in_array($md5, self::$cssScriptHistory))
            return '';
        self::$cssScriptHistory[] = $md5;
        return "<script type=\"text/javascript\">\n/*<![CDATA[*/\n{$script}\n/*]]>*/\n</script>";
    }

    /**
     * @param string $name
     * @return string
     */
    public function mpfScriptFile($name) {
        return $this->scriptFile(AssetsPublisher::get()->mpfAssetFile('scripts/' . $name));
    }

    /**
     * Returns a script tag that links to the specified file
     * @param mixed $path
     * @return string
     */
    public function scriptFile($path) {
        if ('http' != substr($path, 0, 4)) {
            $path = \mpf\WebApp::get()->request()->getWebRoot() . $path;
        }

        $md5 = md5($path);
        if (in_array($md5, self::$cssScriptHistory))
            return '';
        self::$cssScriptHistory[] = $md5;
        return '<script type="text/javascript" src="' . self::encode($path) . '"></script>';
    }

    /**
     * @param string $name
     * @param string $media
     * @return string
     */
    public function mpfCssFile($name, $media = '') {
        return $this->cssFile(AssetsPublisher::get()->mpfAssetFile('styles/' . $name), $media);
    }

    /**
     * Returns a link tag to a css file.
     * @param mixed $path
     * @param string $media
     * @return string
     */
    public function cssFile($path, $media = '') {
        if ('http' != substr($path, 0, 4)) {
            $path = \mpf\WebApp::get()->request()->getWebRoot() . $path;
        }
        $md5 = md5($path);
        if (in_array($md5, self::$cssScriptHistory))
            return '';
        self::$cssScriptHistory[] = $md5;
        if ($media !== '')
            $media = ' media="' . $media . '"';
        return '<link rel="stylesheet" type="text/css" href="' . self::encode($path) . '"' . $media . ' />';
    }

    /**
     * Escapes a text for html.
     * @param string $text
     * @return string
     */
    public function encode($text) {
        return htmlspecialchars($text);
    }

    /**
     * Unescape a html text.
     * @param string $text
     * @return string
     */
    public function decode($text) {
        return htmlspecialchars_decode($text);
    }

    /**
     * Encodes an array of strings.
     * @param array $data
     * @return array
     */
    public function encodeArray($data) {
        $d = array();
        foreach ($data as $key => $value) {
            if (is_string($key))
                $key = htmlspecialchars($key);
            if (is_string($value))
                $value = htmlspecialchars($value);
            elseif (is_array($value))
                $value = self::encodeArray($value);
            $d[$key] = $value;
        }
        return $d;
    }

    /**
     * @param string $content
     * @param null|string $name
     * @param null|string $httpEquiv
     * @param array $options
     * @return string
     */
    public function metaTag($content, $name = null, $httpEquiv = null, $options = array()) {
        if ($name !== null)
            $options['name'] = $name;
        if ($httpEquiv !== null)
            $options['http-equiv'] = $httpEquiv;
        $options['content'] = $content;
        return $this->tag('meta', '', $options);
    }

    /**
     * @param string $relation
     * @param string $type
     * @param string $href
     * @param string $media
     * @param array $options
     * @return string
     */
    public function linkTag($relation = null, $type = null, $href = null, $media = null, $options = array()) {
        if ($relation !== null)
            $options['rel'] = $relation;
        if ($type !== null)
            $options['type'] = $type;
        if ($href !== null)
            $options['href'] = $href;
        if ($media !== null)
            $options['media'] = $media;
        return $this->noContentElement('link', $options);
    }

    /**
     * Html::noContentElement()
     *
     * Generates element that doesn't contain any text. Example: <br />, <input />
     * @param string $element
     * @param array $htmlOptions element's html options like class, style, name or id..
     * @return string element
     */
    public function noContentElement($element, $htmlOptions = array()) {
        $return = "<$element ";
        foreach ($htmlOptions as $name => $value) {
            $return .= "$name='" . str_replace("'", "\'", $value) . "' ";
        }
        return $return . '/>';
    }

    /**
     * Generates a HTML element with selected tag, content and options.
     * @param string $name
     * @param string $content
     * @param array $htmlOptions
     * @return string
     */
    public function tag($name, $content, $htmlOptions = array()) {
        $element = "<$name ";
        foreach ($htmlOptions as $k => $v) {
            $element .= "$k=\"" . self::encode($v) . "\" ";
        }
        return $element . '>' . $content . '</' . $name . '>';
    }

    /**
     * Returns a HTML link to selected URL.
     * @param string|array $href
     * @param string $text
     * @param array $htmlOptions
     * @param boolean $checkAccess
     * @return string
     */
    public function link($href, $text, $htmlOptions = [], $checkAccess = true) {
        if ($checkAccess && is_array($href)){
            $module = isset($href[3])?$href[3]:((isset($href[2]) && is_string($href[2]))?$href[2]:null);
            if (!WebApp::get()->hasAccessTo($href[0], isset($href[1])?$href[1]:null, $module)){
                return ""; // is not visible;
            }
        }
        if (is_array($href)){
            $module = isset($href[3])?$href[3]:((isset($href[2]) && is_string($href[2]))?$href[2]:null);
            $href = WebApp::get()->request()->createURL($href[0], isset($href[1])?$href[1]:null, (isset($href[2])&&is_array($href[2]))?$href[2]:[], $module);
        }
        $htmlOptions['href'] = $href;
        return $this->tag('a', $text, $htmlOptions);
    }

    /**
     * @param string $url
     * @param string $title
     * @param array $htmlOptions
     * @return string
     */
    public function image($url, $title = '', $htmlOptions = []) {
        $htmlOptions['title'] = $title;
        $htmlOptions['src'] = $url;
        return $this->noContentElement('img', $htmlOptions);
    }

    /**
     * @param string $name
     * @param string $title
     * @param array $htmlOptions
     * @return string
     */
    public function mpfImage($name, $title = '', $htmlOptions = []) {
        return $this->image(AssetsPublisher::get()->mpfAssetFile('images/' . $name), $title, $htmlOptions);
    }

    /**
     * It creates a link and a hidden form. That form will be submitted where the link is setup as URL.
     *
     * @param string|array $url Url where form will be submitted
     * @param string $text Visible text for the link
     * @param array $postData List of hidden inputs that will be sent in form and the value for each.
     * @param array $htmlOptions An associative list of htmloptions. Name and value.
     * @param bool $checkAccess If $url is array then it will check if it has access to that page and if not it will not display the link
     * @param bool|string $confirm Use confirmation message. Can be false or string with the message for confirmation
     * @return string
     */
    public function postLink($url, $text, $postData = [], $htmlOptions = [], $checkAccess = true, $confirm = false){
        if ($checkAccess && is_array($url)){
            $module = isset($url[3])?$url[3]:((isset($url[2]) && is_string($url[2]))?$url[2]:null);
            if (!WebApp::get()->hasAccessTo($url[0], isset($url[1])?$url[1]:null, $module)){
                return ""; // is not visible;
            }
        }
        if (is_array($url)){
            $module = isset($url[3])?$url[3]:((isset($url[2]) && is_string($url[2]))?$url[2]:null);
            $url = WebApp::get()->request()->createURL($url[0], isset($url[1])?$url[1]:null, (isset($url[2])&&is_array($url[2]))?$url[2]:[], $module);
        }
        $uniqueID = uniqid("post-link-form");

        $htmlOptions['onclick'] = (isset($htmlOptions['onclick'])?$htmlOptions['onclick']. ' ':'') . 'document.getElementById(\''.$uniqueID.'\').submit(); return false;';
        if ($confirm){
            $htmlOptions['onclick'] = "if (confirm('$confirm')) { $htmlOptions[onclick] }; return false;";
        }
        $r = $this->link($url, $text, $htmlOptions, false);
        $r .= Form::get()->openForm(['style' => 'display:none;', 'method' => 'post', 'id' => $uniqueID, 'action' => $url]);
        foreach ($postData as $name=>$value){
            $r .= Form::get()->hiddenInput($name, $value);
        }
        $r .= Form::get()->closeForm();

        return $r;
    }

    protected $ajaxLinkCount = 1;

    /**
     * Generates a link that sends data using an ajax request to the selected url. On success it will call $callbackFunction and it will send 3
     * params: the received data, postData and clicked element.
     *
     * @param string|array $url
     * @param string $text
     * @param string $callbackFunction
     * @param array $postData
     * @param array $htmlOptions
     * @param bool $checkAccess
     * @param bool $confirm
     * @return string
     */
    public function ajaxLink($url, $text, $callbackFunction, $postData = [], $htmlOptions = [], $checkAccess = true, $confirm  =false){
        if ($checkAccess && is_array($url)){
            $module = isset($url[3])?$url[3]:((isset($url[2]) && is_string($url[2]))?$url[2]:null);
            if (!WebApp::get()->hasAccessTo($url[0], isset($url[1])?$url[1]:null, $module)){
                return ""; // is not visible;
            }
        }
        $this->ajaxLinkCount++;
        $id = isset($htmlOptions['id'])?$htmlOptions['id']:'ajax-link-' . $this->ajaxLinkCount;
        $htmlOptions['id'] = $id;
        if (is_array($url)){
            $module = isset($url[3])?$url[3]:((isset($url[2]) && is_string($url[2]))?$url[2]:null);
            $url = WebApp::get()->request()->createURL($url[0], isset($url[1])?$url[1]:null, (isset($url[2])&&is_array($url[2]))?$url[2]:[], $module);
        }
        $postData[WebApp::get()->request()->getCsrfKey()] = WebApp::get()->request()->getCsrfValue();
        $postData = json_encode($postData);
        $confirm = $confirm?'if (!confirm(\''.$confirm.'\')) { return false; }':'';
        $script = <<<SCRIPT
$('#$id').click(function(){
    $confirm
    var _self = this;
    var postData = $.parseJSON('$postData');
    $.post('$url', postData, function(data){
        return $callbackFunction(data, postData, _self);
    });
    return false;
});
SCRIPT;
        return $this->link('#', $text, $htmlOptions, false) . $this->script($script);
    }

}
