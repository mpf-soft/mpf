<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 07.07.2017
 * Time: 14:21
 */

namespace mpf\helpers;


use mpf\base\AdvancedMethodCallTrait;
use mpf\base\LogAwareObject;

/**
 * Class OpenGraph
 * @package mpf\helpers
 *
 * @method setTitle($title)
 * @method setType($type)
 * @method setUrl($url)
 * @method setDescription($description)
 * @method setDeterminer($word)
 * @method setLocale($locale)
 */
class OpenGraph extends LogAwareObject
{
    use AdvancedMethodCallTrait;

    protected $objectSettings = [];

    public function __set($name, $value)
    {
        if (is_array($value) && method_exists($this, 'set' . ucfirst($name))) {
            $this->callMethod('set' . ucfirst($name), $value);
        } else {
            $this->objectSettings[$name] = $value;
        }
    }

    /**
     * @param string $name
     * @param string[] $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if ('set' == substr($name, 0, 3)) {
            $name = lcfirst(substr($name, 3));
        }
        $this->objectSettings[$name] = $arguments[0];
        return $this;
    }

    /**
     * @param string $url
     * @param string $secureURL
     * @param string $type
     * @param string $width
     * @param string $height
     * @param string[] $others
     * @return $this
     */
    public function setImage($url, $secureURL = null, $type = null, $width = null, $height = null, $others = [])
    {
        $this->objectSettings['image'] = $url;

        if ($secureURL)
            $this->objectSettings['image:secure_url'] = $secureURL;

        if ($type)
            $this->objectSettings['image:type'] = $type;

        if ($width)
            $this->objectSettings['image:width'] = $width;

        if ($height)
            $this->objectSettings['image:height'] = $height;

        foreach ($others as $k => $v) {
            $this->objectSettings['image:' . $k] = $v;
        }

        return $this;
    }

    /**
     * @param string $url
     * @param string $secureURL
     * @param string $type
     * @param string[] $others
     * @return $this
     */
    public function setAudio($url, $secureURL = null, $type = null, $others = [])
    {
        $this->objectSettings['audio'] = $url;

        if ($secureURL)
            $this->objectSettings['audio:secure_url'] = $secureURL;

        if ($type)
            $this->objectSettings['audio:type'] = $type;

        foreach ($others as $k => $v) {
            $this->objectSettings['audio:' . $k] = $v;
        }

        return $this;
    }

    /**
     * @param string $url
     * @param string $secureURL
     * @param string $type
     * @param string $width
     * @param string $height
     * @param string[] $others
     * @return $this
     */
    public function setVideo($url, $secureURL = null, $type = null, $width = null, $height = null, $others = [])
    {
        $this->objectSettings['video'] = $url;

        if ($secureURL)
            $this->objectSettings['video:secure_url'] = $secureURL;

        if ($type)
            $this->objectSettings['video:type'] = $type;

        if ($width)
            $this->objectSettings['video:width'] = $width;

        if ($height)
            $this->objectSettings['video:height'] = $height;

        foreach ($others as $k => $v) {
            $this->objectSettings['video:' . $k] = $v;
        }

        return $this;
    }

    /**
     * @param string[] $list
     * @return $this
     */
    public function setLocalesAlternate($list)
    {
        $this->objectSettings['locale:alternate'] = $list;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setSiteName($name)
    {
        $this->objectSettings['site_name'] = $name;
        return $this;
    }

    public function setArticle($publishedTime = null, $modifiedTime = null, $expirationTime = null, $author = null, $section = null, $tag = null)
    {

        return $this;
    }

    /**
     * @param null $author
     * @param null $isbn
     * @param null $releaseDate
     * @param null $tag
     * @return $this
     */
    public function setBook($author = null, $isbn = null, $releaseDate = null, $tag = null)
    {
        if ($author)
            $this->objectSettings['book:author'] = $author;
        if ($isbn)
            $this->objectSettings['book:isbn'] = $isbn;
        if ($releaseDate)
            $this->objectSettings['book:release_date'] = $releaseDate;
        if ($tag)
            $this->objectSettings['book:tag'] = $tag;
        return $this;
    }

    /**
     * @param null $firstName
     * @param null $lastName
     * @param null $username
     * @param null $gender
     * @return $this
     */
    public function setProfile($firstName = null, $lastName = null, $username = null, $gender = null)
    {
        if ($firstName)
            $this->objectSettings['profile:first_name'] = $firstName;
        if ($lastName)
            $this->objectSettings['profile:last_name'] = $firstName;
        if ($username)
            $this->objectSettings['profile:username'] = $firstName;
        if ($gender)
            $this->objectSettings['profile:gender'] = $firstName;
        return $this;
    }

    /**
     * Display in header the entire metadata;
     * @return string
     */
    public function display()
    {
        $headers = []; //
        foreach ($this->objectSettings as $k => $v) {
            $headers[] = "<meta property=\"og:$k\" content=\"$v\" />";
        }

        return implode("\n", $headers);
    }

    protected function applyParamsToClass()
    {
        return false;
    }
}