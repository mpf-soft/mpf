<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 13.10.2014
 * Time: 16:05
 */

namespace mpf\datasources\sql;


/**
 * Class DataProvider
 * @package mpf\datasources\sql
 */
class DataProvider extends \mpf\datasources\DataProvider {
    /**
     * @var ModelCondition
     */
    public $modelCondition;

    protected function init($config = array()) {
        $this->updateLabels();
        $this->updateKeys();
        $this->checkPage();
        return parent::init($config);
    }

    protected function calculateData() {
        $model = $this->modelCondition->model;
        $this->modelCondition->limit = $this->perPage;
        $this->modelCondition->offset = $this->perPage * ($this->activePage - 1);
        $this->modelCondition->order = $this->order;
        $this->rows = $model::findAll($this->modelCondition);
        $this->modelCondition->limit = '';
        $this->modelCondition->offset = '';
        $this->pk = $model::getDb()->getTablePk($model::getTableName());

        if (!$this->totalResults) {
            $this->calculateTotalResults();
        } elseif (!$this->totalPages) {
            $this->totalPages = (int)($this->totalResults / $this->perPage) + (($this->totalResults % $this->perPage) ? 1 : 0);
        }
    }

    protected function calculateTotalResults() {
        $model = $this->modelCondition->model;
        $this->totalResults = $model::count($this->modelCondition);
        $this->totalPages = (int)($this->totalResults / $this->perPage) + (($this->totalResults % $this->perPage) ? 1 : 0);
    }

    protected function updateLabels() {
        $model = $this->modelCondition->model;
        $labels = $model::getLabels();
        $this->labels = $this->labels ? $this->labels : array();
        foreach ($labels as $k => $v) {
            $this->labels[$k] = $v;
        }
    }

    protected function updateKeys() {
        $model = explode("\\", $this->modelCondition->model);
        $model = $model[count($model) - 1];
        $this->filtersKey = $this->filtersKey ? $this->filtersKey : $model;
        $this->pageGetKey = $this->pageGetKey ? $this->pageGetKey : $model . '_page';
        $this->orderGetKey = $this->orderGetKey ? $this->orderGetKey : $model . '_order';
        $this->perPageChangeKey = $this->perPageChangeKey ? $this->perPageChangeKey : $model . '_perPage';
        $this->perPageSessionKey = $this->perPageSessionKey ? $this->perPageSessionKey : 'SQLDataProvider_' . $model . '_perPage';
        $this->perPageCookieKey = $this->perPageCookieKey ? $this->perPageCookieKey : 'SQLDataProvider_' . $model . '_perPage';
    }

    protected function checkPage() {
        if (!$this->activePage) {
            $this->setPage(isset($_GET[$this->pageGetKey]) ? $_GET[$this->pageGetKey] : 1);
        }

        if (isset($_GET[$this->orderGetKey])) {
            $column = str_replace(array('__ASC', '__DESC'), '', $_GET[$this->orderGetKey]);
            $order = str_replace($column . '__', '', $_GET[$this->orderGetKey]);
            $this->order = $column . ' ' . $order;
        }
    }

    public function getOrder() {
        return explode(' ', $this->order);
    }

    /**
     * @param $column
     * @param null $table
     * @return string
     * @throws \Exception
     */
    public function getColumnOptions($column, $table=  null){
        $model = $this->modelCondition->model;
        $db = $model::getDb();
        $table = $table?$table:$model::getTableName();
        /* @var $db PDOConnection */
        return $db->getColumnOptions($table, $column);
    }
} 