<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;
use PHPMailer\PHPMailer\Exception;

class TranslationsController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/view', [$this, 'view']);
        $app->map(['GET', 'POST'], '/create', [$this, 'create']);
        $app->map(['GET', 'POST'], '/update/[{id}]', [$this, 'update']);
        $app->map(['POST'], '/delete/[{id}]', [$this, 'delete']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['view', 'create', 'update', 'delete'],
                'users'=> ['@'],
            ],
            ['deny',
                'users' => ['*'],
            ],
        ];
    }

    public function view($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\TranslationModel();
        $datas = $model->getItems();

        return $this->_container->module->render($response, 'translations/view.html', [
            'datas' => $datas,
            'model' => $model
        ]);
    }

    public function create($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $id = 0;

        $success = false; $errors = []; $data = [];
        if (isset($_POST['Translation'])){
            $data = $_POST['Translation'];

            foreach ($data['translated_text'] as $lang_id => $t_text) {
                $model = new \ExtensionsModel\ProductCategoryModel('create');
                $model->original_text = $data['original_text'];
                $model->language_id = $lang_id;
                $model->translated_text = $t_text;
                $model->created_at = date('Y-m-d H:i:s');
                $model->updated_at = date('Y-m-d H:i:s');
                $create = \ExtensionsModel\TranslationModel::model()->save(@$model);
                if ($create <= 0) {
                    $message = \ExtensionsModel\TranslationModel::model()->getErrors(false);
                    array_push($errors, $message);
                } else {
                    /*$lmodel = \ExtensionsModel\PostLanguageModel::model()->findByPk($lang_id);
                    if ($lmodel instanceof \RedBeanPHP\OODBBean) {
                        $this->modify_json_data($lmodel->code, $data['original_text'], $t_text);
                    }*/
                }
            }

            if (count($errors) == 0) {
                $this->refresh_data();
                $message = 'Your data has been successfully created.';
                $success = true;
            } else {
                $success = false;
                $message = implode(", ", $errors);
            }
        }

        return $this->_container->module->render($response, 'translations/create.html', [
            'message' => ($message) ? $message : null,
            'success' => $success,
            'data' => $data
        ]);
    }

    public function update($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $params = $request->getParams();

        if (empty($args['id']))
            return false;

        $mdl = \ExtensionsModel\TranslationModel::model()->findByPk($args['id']);

        $success = false; $errors = [];
        if (isset($_POST['Translation'])){
            foreach ($params['Translation'] as $lang_id => $t_val) {
                $model = \ExtensionsModel\TranslationModel::model()->findByAttributes(['language_id' => $lang_id, 'original_text' => $mdl->original_text]);
                if ($model instanceof \RedBeanPHP\OODBBean && $model->translated_text != $t_val) {
                    $model->translated_text = $t_val;
                    $model->updated_at = date('Y-m-d H:i:s');
                    $update = \ExtensionsModel\TranslationModel::model()->update($model);
                    if ($update > 0) {
                        /*$lmodel = \ExtensionsModel\PostLanguageModel::model()->findByPk($lang_id);
                        if ($lmodel instanceof \RedBeanPHP\OODBBean) {
                            $this->modify_json_data($lmodel->code, $mdl->original_text, $t_val);
                        }*/
                        $success = true;
                    } else {
                        $message = \ExtensionsModel\TranslationModel::model()->getErrors(false);
                        array_push($errors, $message);
                    }
                }
            }

            if (count($errors) == 0) {
                $this->refresh_data();
                return json_encode(['success' => true, 'message' => 'Your data has been successfully updated.']);
            } else {
                return json_encode(['success' => false, 'message' => implode(", ", $message)]);
            }
        }
    }

    public function delete($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $model = \ExtensionsModel\TranslationModel::model()->findByPk($args['id']);
        $original_text = $model->original_text;
        $delete = \ExtensionsModel\TranslationModel::model()->delete($model);
        if ($delete) {
            $delete2 = \ExtensionsModel\TranslationModel::model()->deleteAllByAttributes(['original_text' => $original_text]);
            $this->refresh_data();
            /*$langs = \ExtensionsModel\PostLanguageModel::model()->findAll();
            foreach ($langs as $lang) {
                $this->modify_json_data($lang->code, $original_text, null);
            }*/
            $message = 'Your data has been successfully deleted.';
            echo true;
        }
    }

    private function modify_json_data($lang, $original_text, $translated_val = null) {
        $fname = $this->_settings['basePath'].'/data/trans_'.$lang.'.json';
        if (!file_exists($fname)) {
            // create new file
            $file = fopen($fname, 'w');
            throw new \Exception("Translation file not found");
        }

        $content = file_get_contents($fname);
        $trans_data = json_decode($content, true);

        if (is_array($trans_data)){
            if (!empty($translated_val)) {
                $trans_data[$original_text] = $translated_val;
            } else {
                unset($trans_data[$original_text]);
            }
        } else {
            $trans_data = [$original_text => $translated_val];
        }

        try {
            file_put_contents($fname, json_encode($trans_data));
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
    }

    private function refresh_data() {
        $langs = \ExtensionsModel\PostLanguageModel::model()->findAll();
        foreach ($langs as $lang) {
            $trans_data = \ExtensionsModel\TranslationModel::model()->findAllByAttributes(['language_id' => $lang->id]);
            $items = [];
            foreach ($trans_data as $data) {
                $items[$data->original_text] = $data->translated_text;
            }

            $fname = $this->_settings['basePath'].'/data/trans_'.$lang->code.'.json';
            if (!file_exists($fname)) {
                // create new file
                $file = fopen($fname, 'w');
                throw new \Exception("Translation file not found");
            }

            try {
                file_put_contents($fname, json_encode($items));
            } catch (Exception $e) {
                var_dump($e->getMessage());
            }
        }

        return true;
    }
}