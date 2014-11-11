<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 13.10.2014
 * Time: 15:41
 */

namespace mpf\datasources;


use mpf\base\Object;
use mpf\web\Cookie;
use mpf\web\helpers\Html;
use mpf\web\Session;
use mpf\WebApp;

abstract class DataProvider extends Object {

    /**
     * Name of the primary key
     * @var string
     */
    public $pk = 'id';

    public $perPageChangeKey;

    public $perPageSessionKey;

    public $perPageCookieKey;

    /**
     * Name of the key for page number from $_GET list. If it's not set the auto
     * name is composed from model name. ("{$ModelName}_page")
     * @var string
     */
    public $pageGetKey;

    /**
     * Name of the key for page number from $_GET list. If it's not set the auto
     * name is composed from model name. ("{$ModelName}_order")
     * @var string
     */
    public $orderGetKey;

    /**
     * Key used in $_GET or $_POST to send filters for current data.
     * @var string
     */
    public $filtersKey;

    protected $calculated = false;

    public $rows;

    public $totalPages;

    public $totalResults;

    public $activePage;

    public $perPage = 30;

    public $order;

    public $labels;

    public $optionsPerPage = array(5, 10, 15, 20, 30, 50, 100, 150, 300, 500, 1000);

    protected abstract function calculateData();

    public abstract function getOrder();

    public abstract function getColumnOptions($column, $table = null);

    protected function init($config) {
        if ($this->perPageChangeKey && $this->perPageSessionKey) {
            if (isset($_POST[$this->perPageChangeKey]) && is_numeric($_POST[$this->perPageChangeKey])) {
                $this->setLimitPerPage((int)$_POST[$this->perPageChangeKey]);
                WebApp::get()->request()->goBack();
            }
            if (Session::get()->exists($this->perPageSessionKey)) {
                $this->perPage = Session::get()->value($this->perPageSessionKey);
            } elseif ($this->perPageCookieKey && Cookie::get()->exists($this->perPageCookieKey)) {
                Session::get()->set($this->perPageSessionKey, $this->perPage = Cookie::get()->value($this->perPageCookieKey));
            }
        }
        return parent::init($config);
    }

    public function getData() {
        if (!$this->calculated) {
            $this->calculateData();
            $this->calculated = true;
        }
        return $this->rows;
    }

    public function setPage($number) {
        $this->activePage = $number;
    }

    public function setLimitPerPage($limit) {
        $this->perPage = $limit;
        if ($this->perPageSessionKey) {
            Session::get()->set($this->perPageSessionKey, $limit);
        }
        if ($this->perPageCookieKey){
            Cookie::get()->set($this->perPageCookieKey, $limit);
        }
    }

    public function getPagesNumber() {
        return $this->totalPages;
    }

    public function getResultsNumber() {
        return $this->totalResults;
    }

    public function getCurrentPage() {
        return $this->activePage;
    }

    public function getLabels() {
        return $this->labels;
    }

    public function getLabel($column) {
        return isset($this->labels[$column]) ? $this->labels[$column] : ucwords(str_replace('_', ' ', $column));
    }

    public function getPageKey() {
        return $this->pageGetKey;
    }

    public function getPkKey(){
        return $this->pk;
    }

    /**
     * Generates a link to change order for this data provider using the order get Key that can be generated automatically or manually by a developer.
     * @param $column
     * @param $label
     * @param array $htmlOptions
     * @return string
     */
    public function getColumnOrderLink($column, $label, $htmlOptions = array()) {
        return Html::get()->link($this->getColumnOrderURL($column), $label, $htmlOptions);
    }

    public function getColumnOrderURL($column) {
        $order = 'ASC';
        if (isset($_GET[$this->orderGetKey])) {
            $col = str_replace(array('__ASC', '__DESC'), '', $_GET[$this->orderGetKey]);
            $order = str_replace($column . '__', '', $_GET[$this->orderGetKey]);
            if ($col == $column) {
                $order = ('ASC' == $order) ? 'DESC' : 'ASC';
            } else {
                $order = 'ASC';
            }
        }
        $params = WebApp::get()->request()->getParams();
        $params[$this->orderGetKey] = $column . '__' . $order;
        return WebApp::get()->request()->createURL(WebApp::get()->request()->getController(), WebApp::get()->request()->getAction(), $params);
    }

    /**
     * Generates link for a selected page using the GET key generated automatically or manually by a developer.
     * @param $pageNumber
     * @param $label
     * @param array $htmlOptions
     * @return string
     */
    public function getLinkForPage($pageNumber, $label, $htmlOptions = array()) {
        return Html::get()->link($this->getURLForPage($pageNumber), $label, $htmlOptions);
    }

    public function getURLForPage($pageNumber) {
        $params = WebApp::get()->request()->getParams();
        $params[$this->pageGetKey] = $pageNumber;
        return WebApp::get()->request()->createURL(WebApp::get()->request()->getController(), WebApp::get()->request()->getAction(), $params);
    }
} 