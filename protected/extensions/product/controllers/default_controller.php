<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;
use PHPMailer\PHPMailer\Exception;

class ProductDefaultController extends BaseController
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

        $datas = \ExtensionsModel\ProductModel::model()->findAll();

        return $this->_container->module->render($response, 'products/view.html', [
            'datas' => $datas
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

        $model = new \ExtensionsModel\ProductModel('create');
        $categories = \ExtensionsModel\ProductCategoryModel::model()->findAll();
        $id = 0;

        $success = false; $errors = []; $data = [];
        if (isset($_POST['Product'])){
            $data = $_POST['Product'];

            if (empty($_POST['Product']['title'])) {
                array_push($errors, 'Title is required.');
            }

            $model->title = $_POST['Product']['title'];
            $model->category_id = $_POST['Product']['category_id'];
            $model->status = $_POST['Product']['status'];
            $model->description = $_POST['Product']['description'];
            $model->created_at = date('Y-m-d H:i:s');
            $model->updated_at = date('Y-m-d H:i:s');
            if (count($errors) == 0) {
                $create = \ExtensionsModel\ProductModel::model()->save(@$model);
                if ($create > 0) {
                    $message = 'Your data has been successfully created.';
                    $success = true;
                    $id = $model->id;
                } else {
                    $message = 'Failed to create new data.';
                    $success = false;
                }
            } else {
                $success = false;
                $message = implode(", ", $errors);
            }
        }

        return $this->_container->module->render($response, 'products/create.html', [
            'categories' => $categories,
            'message' => ($message) ? $message : null,
            'success' => $success,
            'id' => $id,
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

        if (empty($args['id']))
            return false;

        $model = \ExtensionsModel\ProductModel::model()->findByPk($args['id']);
        $categories = \ExtensionsModel\ProductCategoryModel::model()->findAll();

        $success = false; $errors = []; $data = [];
        if (isset($_POST['Product'])){
            $data = $_POST['Product'];
            if (empty($_POST['Product']['title'])) {
                array_push($errors, 'Title is required.');
            }

            $model->title = $_POST['Product']['title'];
            $model->category_id = $_POST['Product']['category_id'];
            $model->status = $_POST['Product']['status'];
            $model->description = $_POST['Product']['description'];
            $model->updated_at = date('Y-m-d H:i:s');
            if (count($errors) == 0) {
                $update = \ExtensionsModel\ProductModel::model()->update($model);
                if ($update > 0) {
                    $message = 'Your data has been successfully updated.';
                    $success = true;
                    $slide_id = $model->id;
                } else {
                    $message = 'Failed to update data.';
                    $success = false;
                }
            } else {
                $success = false;
                $message = implode(", ", $errors);
            }
        }

        return $this->_container->module->render($response, 'products/update.html', [
            'categories' => $categories,
            'message' => ($message) ? $message : null,
            'success' => $success,
            'id' => $model->id,
            'model' => $model
        ]);
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

        $model = \ExtensionsModel\ProductModel::model()->findByPk($args['id']);
        $delete = \ExtensionsModel\ProductModel::model()->delete($model);
        if ($delete) {
            $message = 'Your data has been successfully deleted.';
            echo true;
        }
    }
}