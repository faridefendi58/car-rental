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
        $app->map(['POST'], '/upload-images', [$this, 'get_upload_images']);
        $app->map(['POST'], '/delete-image/[{id}]', [$this, 'delete_image']);
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

        $pmodel = new \ExtensionsModel\ProductModel();
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

        $productImages = new \ExtensionsModel\ProductImagesModel();
        $images = $pmodel->getImages(['id' => $model->id]);

        return $this->_container->module->render($response, 'products/update.html', [
            'categories' => $categories,
            'message' => ($message) ? $message : null,
            'success' => $success,
            'id' => $model->id,
            'model' => $model,
            'productImages' => $productImages,
            'images' => $images
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
            $images = \ExtensionsModel\ProductImagesModel::model()->findAllByAttributes(['product_id' => $args['id']]);
            if (count($images) > 0) {
                foreach ($images as $image) {
                    $path = $this->_settings['basePath'].'/../'.$image->upload_folder.'/'.$image->file_name;
                    $delete = \ExtensionsModel\ProductImagesModel::model()->delete($image);
                    if ($delete) {
                        if (file_exists($path))
                            unlink($path);
                    }
                }
            }
            $message = 'Your data has been successfully deleted.';
            echo true;
        }
    }

    public function get_upload_images($request, $response, $args)
    {
        if ($this->_user->isGuest()){
            return $response->withRedirect($this->_login_url);
        }

        if (isset($_POST['ProductImages'])) {
            $path_info = pathinfo($_FILES['ProductImages']['name']['file_name']);
            if (!in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                echo json_encode(['status'=>'failed','message'=>'Allowed file type are jpg, png']); exit;
                exit;
            }
            $model = new \ExtensionsModel\ProductImagesModel();
            $model->product_id = $_POST['ProductImages']['product_id'];
            $model->type = $_POST['ProductImages']['type'];
            $model->upload_folder = 'uploads/images/products';
            $model->file_name = time().'.'.$path_info['extension'];
            $model->alt = $_POST['ProductImages']['alt'];
            $model->description = $_POST['ProductImages']['description'];
            $model->created_at = date("Y-m-d H:i:s");
            $create = \ExtensionsModel\ProductImagesModel::model()->save(@$model);
            if ($create > 0) {
                $uploadfile = $model->upload_folder . '/' . $model->file_name;
                move_uploaded_file($_FILES['ProductImages']['tmp_name']['file_name'], $uploadfile);
                echo json_encode(['status'=>'success','message'=>'Successfully uploaded new images']); exit;
            }
        }

        echo json_encode(['status'=>'failed','message'=>'Unable to upload the files.']); exit;
        exit;
    }

    public function delete_image($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (!isset($_POST['id'])) {
            return false;
        }

        $model = \ExtensionsModel\ProductImagesModel::model()->findByPk($_POST['id']);
        $path = $this->_settings['basePath'].'/../'.$model->upload_folder.'/'.$model->file_name;
        $delete = \ExtensionsModel\ProductImagesModel::model()->delete($model);
        if ($delete) {
            if (file_exists($path))
                unlink($path);
            echo true;
        }
        exit;
    }
}