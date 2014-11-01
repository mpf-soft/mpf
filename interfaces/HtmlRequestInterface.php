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

namespace mpf\interfaces;

interface HtmlRequestInterface extends LogAwareObjectInterface{

    /**
     * Must return instantiated component;
     * @return HtmlRequestInterface
     */
    public static function get($options = array());

    /**
     * Get current controller name
     * @return string
     */
    public function getController();

    /**
     * Get current action name
     * @return string
     */
    public function getAction();

    /**
     * Get an associative list of parameters and values;
     * @return array
     */
    public function getParams();

    /**
     * Get active module name;
     * @return string
     */
    public function getModule();
    
    /**
     * Get url to the selected page;
     * @param string $controller Name of the controller where the URL must link
     * @param string $action Name of the action where the URL must link
     * @param array $params List of associative parameters 
     * @param string $module Name of the module where the URL must link. If none it's set it will use current module;
     * @return string
     */
    public function createURL($controller, $action = null, $params = array(), $module = null);
    
    /**
     * Get full current URL;
     * @return string
     */
    public function getCurrentURL();
    
    /**
     * Redirect to selected string URL;
     * @return null
     */
    public function goToURL($url);
    
    /**
     * Redirect to generated internal URL;
     * @return null
     */
    public function goToPage($controller, $action = null, $params = array());
    
    /**
     * Reload current page
     * @return null
     */
    public function reloadPage();
    
    /**
     * Go back to the last page
     * @return null
     */
    public function goBack();
    
    /**
     * Get URL referer. Similar to goBack(), but instead of redirect will return the address;
     * @return string
     */
    public function getReferrer();
    
    /**
     * Will replace current HTML request with selected URI. This is used by HtmlRequest 
     * unit test.
     * @return null
     */
    public function simulateURI($uri);
    
    /**
     * Change current controller.
     * @param string $name
     */
    public function setController($name);
    
    /**
     * Change current action.
     * @param string $name
     */
    public function setAction($name);
    
    /**
     * Get html url for website root to be used to include images, scripts and other
     * media elements
     * @return string
     */
    public function getWebRoot();
    
    /**
     * Get html url for link root to be used to manually create links
     * @return string
     */
    public function getLinkRoot();

    /**
     * Checks if website is accessed using a secure connection.
     * @return boolean
     */
    public function isSecureConnection();

    /**
     * Checks if this is a post request or not.
     * @return boolean
     */
    public function isPostRequest();

    /**
     * Checks if this is a put request or not.
     * @return boolean
     */
    public function isPutRequest();

    /**
     * Checks if this is a delete request or not.
     * @return boolean
     */
    public function isDeleteRequest();

    /**
     * Checks if this is an ajax request or not.
     * @return boolean
     */
    public function isAjaxRequest();

    /**
     * Get preferred language from client browser settings.
     * @return string
     */
    public function getPreferredLanguage();

    /**
     * Send a file for download.
     * @param string $fileName
     * @param string $content
     * @param null|string $mimeType
     * @param bool $terminate
     * @return null
     */
    public function sendFile($fileName, $content, $mimeType = null, $terminate = true);

    /**
     * Send a file for download using more advanced options. Details on HTML request class.
     * @param string $filePath
     * @param array $options
     * @return null
     */
    public function xSendFile($filePath, $options = array());

    /**
     * Return visitor userAgent name.
     * @return string
     */
    public function getUserAgent();
}
