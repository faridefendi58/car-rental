<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;
use PHPMailer\PHPMailer\Exception;

class ProductCategoryController extends BaseController
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
        $app->map(['POST'], '/create-product', [$this, 'create_product']);
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

        $model = new \ExtensionsModel\ProductCategoryModel();
        $datas = \ExtensionsModel\ProductCategoryModel::model()->findAll();

        return $this->_container->module->render($response, 'products/category_view.html', [
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

        $model = new \ExtensionsModel\ProductCategoryModel('create');
        $id = 0;

        $success = false; $errors = []; $data = [];
        if (isset($_POST['ProductCategory'])){
            $data = $_POST['ProductCategory'];
            if (!empty($_FILES['ProductCategory'])) {
                $path_info = pathinfo($_FILES['ProductCategory']['name']['image']);
                if (!in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                    array_push($errors, 'Image file should be in jpg or png format.');
                }

                $upload_folder = 'uploads/images/products';
                $file_name = 'cat_'. time().'.'.$path_info['extension'];
                $model->image = $upload_folder .'/'. $file_name;
            }

            if (empty($_POST['ProductCategory']['title'])) {
                array_push($errors, 'Title is required.');
            }

            $model->title = $_POST['ProductCategory']['title'];
            if (isset($_POST['ProductCategory']['slug'])) {
                $model->slug = $_POST['ProductCategory']['slug'];
            } else {
                $pmodel = new \ExtensionsModel\PostModel();
                $model->slug = $pmodel->createSlug($model->title);
            }
            $model->description = $_POST['ProductCategory']['description'];
            $model->created_at = date('Y-m-d H:i:s');
            $model->updated_at = date('Y-m-d H:i:s');
            if (count($errors) == 0) {
                $create = \ExtensionsModel\ProductCategoryModel::model()->save(@$model);
                if ($create > 0) {
                    try {
                        $uploadfile = $upload_folder . '/' . $file_name;
                        move_uploaded_file($_FILES['ProductCategory']['tmp_name']['image'], $uploadfile);
                    } catch (Exception $e) {}

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

        return $this->_container->module->render($response, 'products/category_create.html', [
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

        $model = \ExtensionsModel\ProductCategoryModel::model()->findByPk($args['id']);

        $success = false; $errors = []; $data = [];
        if (isset($_POST['ProductCategory'])){
            $data = $_POST['ProductCategory'];
            $old_image = $model->image;
            if (!empty($_FILES['ProductCategory']['name']['image'])) {
                $path_info = pathinfo($_FILES['ProductCategory']['name']['image']);
                if (!in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                    array_push($errors, 'Image file should be in jpg or png format.');
                }

                $upload_folder = 'uploads/images/products';
                $file_name = 'cat_'. time().'.'.$path_info['extension'];
                $model->image = $upload_folder .'/'. $file_name;
            }

            if (empty($_POST['ProductCategory']['title'])) {
                array_push($errors, 'Title is required.');
            }

            $model->title = $_POST['ProductCategory']['title'];
            if (isset($_POST['ProductCategory']['slug'])) {
                $model->slug = $_POST['ProductCategory']['slug'];
            } else {
                $pmodel = new \ExtensionsModel\PostModel();
                $model->slug = $pmodel->createSlug($model->title);
            }
            $model->description = $_POST['ProductCategory']['description'];
            $model->updated_at = date('Y-m-d H:i:s');
            if (count($errors) == 0) {
                $update = \ExtensionsModel\ProductCategoryModel::model()->update($model);
                if ($update > 0) {
                    if (!empty($file_name)) {
                        try {
                            $uploadfile = $upload_folder . '/' . $file_name;
                            move_uploaded_file($_FILES['ProductCategory']['tmp_name']['image'], $uploadfile);
                            unlink($old_image);
                        } catch (Exception $e) {}
                    }

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

        return $this->_container->module->render($response, 'products/category_update.html', [
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

        $model = \ExtensionsModel\ProductCategoryModel::model()->findByPk($args['id']);
        $image_file = $model->image;
        $delete = \ExtensionsModel\ProductCategoryModel::model()->delete($model);
        if ($delete) {
            if (!empty($image_file)) {
                try {
                    unlink($image_file);
                } catch (Exception $e) {}
            }
        }
        $message = 'Your data has been successfully deleted.';
        echo true;
    }

    public function create_product($request, $response, $args)
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

        $success = false; $message = null; $errors = []; $data = [];
        if (isset($_POST['Product'])){

            // avoid double execution
            $current_time = time();
            if(isset($_SESSION['Product']) && !empty($_SESSION['Product'])) {
                $selisih = $current_time - $_SESSION['Product'];
                if ($selisih <= 10) {
                    return $response->withJson(
                        [
                            'status' => 'success',
                            'message' => 'Data berhasil disimpan.',
                        ], 201);
                } else {
                    $_SESSION['Product'] = $current_time;
                }
            } else {
                $_SESSION['Product'] = $current_time;
            }

            $data = $_POST['Product'];

            if (empty($_POST['Product']['title'])) {
                array_push($errors, 'Title is required.');
            }

            $model->title = $_POST['Product']['title'];
            if (isset($_POST['Product']['slug'])) {
                $model->slug = $_POST['Product']['slug'];
            } else {
                $pmodel = new \ExtensionsModel\PostModel();
                $model->slug = $pmodel->createSlug($model->title);
            }
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

        return $response->withJson(
            [
                'status' => ($success)? 'success' : 'failed',
                'message' => $message,
                'id' => $id
            ], 201);
    }
}