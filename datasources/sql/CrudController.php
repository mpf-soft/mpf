<?php
/**
 * Created by PhpStorm.
 * User: mirel
 * Date: 16.07.2015
 * Time: 11:40
 */

namespace mpf\datasources\sql;


use app\components\htmltools\Messages;
use mpf\web\Controller;

class CrudController extends Controller {
    protected $_modelShortName;
    public $modelClass;

    /**
     * Action to redirect after save;
     * @var string
     */
    public $afterSaveRedirectTo = 'index';

    public $messages = [
        'saved' => 'Model saved!',
        'deletedSingular' => 'Model deleted!',
        'deletedPlural' => 'Models delted!',
        'created' => 'Model created!'
    ];

    /**
     * Removes namespace from model class name;
     * @return mixed
     */
    protected function getModelShortName() {
        if (!$this->_modelShortName) {
            $m = explode('\\', $this->modelClass);
            $this->_modelShortName = $m[count($m) - 1];
        }
        return $this->_modelShortName;
    }

    public function actionIndex() {
        $model = $this->modelClass;
        $model = $model::model();
        if (isset($_GET[$this->getModelShortName()])) {
            $model->setAttributes($_GET[$this->getModelShortName()]);
        }
        $this->assign('model', $model);
    }

    public function actionView($id){
        $model = $this->modelClass;
        $this->assign('model', $model::findByPk($id));
    }

    public function actionEdit($id) {
        $model = $this->modelClass;
        $model = $model::findByPk($id);
        if (isset($_POST[$this->getModelShortName()])) {
            $model->setAttributes($_POST[$this->getModelShortName()]);
            if ($model->save()) {
                Messages::get()->success($this->messages['saved']);
                $this->goToAction($this->afterSaveRedirectTo, ('index' == $this->afterSaveRedirectTo)?[]:['id' => $model->id]);
            }
        }
        $this->assign('model', $model);
    }

    public function actionDelete() {
        $model = $this->modelClass;
        $models = $model::findAllByPk($_POST[$this->getModelShortName()]);
        foreach ($models as $model) {
            $model->delete();
        }
        Messages::get()->info((1 !== count($models)) ? $this->messages['deletedPlural'] : $this->messages['deletedSingular']);
        $this->request->goBack();
    }

    public function actionCreate() {
        $model = $this->modelClass;
        $model = new $model();
        if (isset($_POST[$this->getModelShortName()])) {
            $model->setAttributes($_POST[$this->getModelShortName()]);
            if ($model->save()) {
                Messages::get()->success($this->messages['created']);
                $this->goToAction($this->afterSaveRedirectTo, ('index' == $this->afterSaveRedirectTo)?[]:['id' => $model->id]);
            }
        }
        $this->assign('model', $model);
    }
}