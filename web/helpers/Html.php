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
     * @param string $href
     * @param string $text
     * @param array $htmlOptions
     * @return string
     */
    public function link($href, $text, $htmlOptions = array()) {
        $htmlOptions['href'] = $href;
        return $this->tag('a', $text, $htmlOptions);
    }

    /**
     * @param string $url
     * @param string $title
     * @param array $htmlOptions
     * @return string
     */
    public function image($url, $title = '', $htmlOptions = array()) {
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
    public function mpfImage($name, $title = '', $htmlOptions = array()) {
        return $this->image(AssetsPublisher::get()->mpfAssetFile('images/' . $name), $title, $htmlOptions);
    }

}
