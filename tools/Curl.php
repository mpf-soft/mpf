<?php
/**
 * Created by PhpStorm.
 * User: Mirel Mitache
 * Date: 29.11.2018
 * Time: 18:20
 */

namespace mpf\tools;

use mpf\base\App;
use mpf\base\LogAwareSingleton;

class Curl  extends LogAwareSingleton {
    /**
     * Location of session file
     * @var string
     */
    public $cookieLocation = "APP_ROOT_temp";

    public $proxyHost;

    public $proxyPort;

    public $userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:40.0) Gecko/20100101 Firefox/40.0';

    protected $error, $currentFile, $lastURL;

    /**
     * If a curl error was found then returns it
     * @return mixed
     */
    public function getError(){
        return $this->error;
    }

    /**
     * Get's current session file path;
     * @return string
     */
    public function getCurrentFile(){
        return $this->currentFile;
    }

    /**
     * Create a session file for specified domain
     * @param $domain
     */
    public function startSession($domain) {
        $this->cookieLocation = str_replace('APP_ROOT', APP_ROOT, $this->cookieLocation);
        $this->currentFile = $this->cookieFileLocation . date('YmdHis') . '-' . md5($domain);
        fclose(fopen($this->currentFile, 'w'));
    }

    /**
     * Create a post request
     * @param string $url
     * @param array $fields
     * @param null $ref
     * @param array $opts
     * @return string
     */
    public function postRequest($url, $fields = [], $ref = null, $opts = []) {
        return $this->_curl($url, $fields, $ref, $opts);
    }

    /**
     * Create a get request
     * @param array $url
     * @param null $ref
     * @param array $opts
     * @return string
     */
    public function getRequest($url, $ref = null, $opts = []) {
        return $this->_curl($url, [], $ref, $opts);
    }

    /**
     * Delete the current session file;
     */
    public function endSession() {
        if ($this->currentFile) {
            unlink($this->currentFile);
            $this->currentFile = false;
        }
    }

    /**
     * Return final url of the last request.
     * @return mixed
     */
    public function getFinalURL(){
        return $this->lastURL;
    }

    /**
     * @param string $url
     * @param string[] $postData
     * @param string $ref
     * @return string
     */
    protected function _curl($url, $postData = [], $ref = null, $opts = []) {
        $this->error = false;
        $curl = curl_init($url);
        $opts[CURLOPT_RETURNTRANSFER] = true;
        $opts[CURLOPT_FOLLOWLOCATION] = true;
        $opts[CURLOPT_SSL_VERIFYPEER] = false;
        $opts[CURLOPT_HEADER] = true;
        if ($this->proxyHost) {
            $opts[CURLOPT_PROXY] = $this->proxyHost;
            $opts[CURLOPT_PROXYPORT] = $this->proxyPort;
        }
        $opts[CURLOPT_REFERER] = $ref ?: $url;
        $opts[CURLOPT_USERAGENT] = isset($opts[CURLOPT_USERAGENT]) ? $opts[CURLOPT_USERAGENT] : $this->userAgent;
        if ($postData) {
            $opts[CURLOPT_POST] = count($postData);
            $opts[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }
        if ($this->currentFile) {
            $opts[CURLOPT_COOKIEJAR] = $this->currentFile;
            $opts[CURLOPT_COOKIEFILE] = $this->currentFile;
        }
        curl_setopt_array($curl, $opts);
        $message = substr(curl_exec($curl), curl_getinfo($curl, CURLINFO_HEADER_SIZE));
        $this->lastURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        if (curl_error($curl)) {
            $this->error = curl_errno($curl) . ':' . curl_error($curl);
            $this->error(curl_error($curl));
        }
        curl_close($curl);
        return $message;
    }
}