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

namespace mpf\web;
use mpf\base\AutoLoader;

/**
 * Description of AssetsPublisher
 *
 * @author mirel
 */
class AssetsPublisher extends \mpf\base\LogAwareObject {

    /**
     *
     * @var AssetsPublisher[string]
     */
    private static $_instances = array();

    /**
     * List of already known published folders / files
     * @var boolean[string]
     */
    private $publishCache = array();

    /**
     * Name of the public folder where the assets can be copied
     * @var string
     */
    public $publishFolder = '__assets';

    /**
     * If set to true then every time the assets will be overwritten with the 
     * new versions.
     * @var boolean
     */
    public $developmentMode = false;

    /**
     * Return instance of class.
     * @param string[string] $config
     * @return AssetsPublisher
     */
    public static function get($config = array()) {
        $hash = md5(serialize($config));
        if (!isset(self::$_instances[$hash]))
            self::$_instances[$hash] = new static($config);
        return self::$_instances[$hash];
    }

    public function mpfAssetFile($name){
        return self::publishFolder(AutoLoader::getLastRegistered()->path('\mpf\__assets', true)) . $name;
    }

    /**
     * Publish an entire folder
     * @param string $path
     * @return string Published URL
     */
    public function publishFolder($path) {
        $newName = dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR .
                $this->publishFolder . DIRECTORY_SEPARATOR . md5($path);
        $url = \mpf\WebApp::get()->request()->getWebRoot() . $this->publishFolder . "/" . md5($path) . "/";
        if ($this->_isPublished($newName))
            return $url;
        $this->_folder($path, $newName);
        $this->publishCache[$newName] = true;
        return $url;
    }

    /**
     * Publish a single file
     * @param string $path
     * @return string Published URL
     */
    public function publishFile($path) {
        $newName = dirname(SCRIPT_FILENAME) . DIRECTORY_SEPARATOR .
                $this->publishFolder . DIRECTORY_SEPARATOR . "files" .
                DIRECTORY_SEPARATOR . md5($path) . '_' . basename($path);
        $url = \mpf\WebApp::get()->request()->getWebRoot() . $this->publishFolder . "/files/" . md5($path) . '_' . basename($path);
        if ($this->_isPublished($newName))
            return $newName;
        $this->_file($path, $newName);
        $this->publishCache[$newName] = true;
        return $url;
    }

    /**
     * Check if selected path was already published
     * @param string $publishPath
     * @return boolean
     */
    private function _isPublished($publishPath) {
        if (isset($this->publishCache[$publishPath]) && $this->publishCache[$publishPath])
            return true;
        if ($this->developmentMode) {
            return false;
        }
        return $this->publishCache[$publishPath] = file_exists($publishPath);
    }

    /**
     * Copy folder contents from one location to another
     * @param string $oldPath
     * @param string $newPath
     */
    protected function _folder($oldPath, $newPath) {
        if (!is_dir($newPath)) {
            $oldumask = umask(0);
            mkdir($newPath, 01777); // so you get the sticky bit set 
            umask($oldumask);
        } elseif (filemtime($oldPath) < filemtime($newPath)) {
            //$this->debug($oldPath . ': ' . filemtime($oldPath). ' < ' . filemtime($newPath));
            return;
        } else {
            //$this->debug($oldPath . ': ' . filemtime($oldPath). ' > ' . filemtime($newPath));
        }
        $dir = @opendir($oldPath) or trigger_error("Unable to open original folder to copy it! ($oldPath)");
        while ($file = readdir($dir)) {
            if ($file != "." && $file != ".." && !is_dir("$oldPath/$file")) {
                copy("$oldPath/$file", "$newPath/$file");
            } elseif ($file != "." && $file != "..") {
                $this->_folder("$oldPath/$file", "$newPath/$file");
            }
        }
        closedir($dir);
    }

    /**
     * Copy a single file
     * @param string $oldPath
     * @param string $newPath
     * @return boolean
     */
    protected function _file($oldPath, $newPath) {
        return copy($oldPath, $newPath);
    }

}
