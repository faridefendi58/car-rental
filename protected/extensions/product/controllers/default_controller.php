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
        $app->map(['GET', 'POST'], '/create-price/[{id}]', [$this, 'create_price']);
        $app->map(['POST'], '/delete-price/[{id}]', [$this, 'delete_price']);
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
            if (isset($_POST['Product']['slug'])) {
                $model->slug = $_POST['Product']['slug'];
            } else {
                $pmodel = new \ExtensionsModel\PostModel();
                $model->slug = $pmodel->createSlug($model->title);
            }
            $model->category_id = $_POST['Product']['category_id'];
            $model->status = $_POST['Product']['status'];
            $model->description = $_POST['Product']['description'];
            $model->description2 = $_POST['Product']['description2'];
            $model->description3 = $_POST['Product']['description3'];
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
        $prices = \ExtensionsModel\ProductPricesModel::model()->findAllByAttributes(['product_id' => $model->id]);

        $success = false; $errors = []; $data = [];
        if (isset($_POST['Product'])){
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
            $model->description2 = $_POST['Product']['description2'];
            $model->description3 = $_POST['Product']['description3'];
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
            'images' => $images,
            'prices' => $prices
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
        }
        echo true;
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

    public function create_price($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = \ExtensionsModel\ProductModel::model()->findByPk($args['id']);

        if (isset($_POST['ProductPrices']) && !empty($_POST['ProductPrices']['product_id'])) {
            // avoid double execution
            $current_time = time();
            if(isset($_SESSION['ProductPrices']) && !empty($_SESSION['ProductPrices'])) {
                $selisih = $current_time - $_SESSION['ProductPrices'];
                if ($selisih <= 10) {
                    return $response->withJson(
                        [
                            'status' => 'success',
                            'message' => 'Data berhasil disimpan.',
                        ], 201);
                } else {
                    $_SESSION['ProductPrices'] = $current_time;
                }
            } else {
                $_SESSION['ProductPrices'] = $current_time;
            }

            foreach ($_POST['ProductPrices']['category_id'] as $i => $title) {
                if (empty($_POST['ProductPrices']['id'][$i])) {
                    $model[$i] = new \ExtensionsModel\ProductPricesModel();
                    $model[$i]->category_id = $_POST['ProductPrices']['category_id'][$i];
                    $model[$i]->product_id = $_POST['ProductPrices']['product_id'];
                    $model[$i]->unit_price = $_POST['ProductPrices']['unit_price'][$i];
                    $model[$i]->discount = $_POST['ProductPrices']['discount'][$i];
                    $model[$i]->period = $_POST['ProductPrices']['period'][$i];
                    $model[$i]->created_at = date("Y-m-d H:i:s");
                    $model[$i]->updated_at = date("Y-m-d H:i:s");
                    if (!empty($model[$i]->category_id) && $model[$i]->unit_price > 0) {
                        $save = \ExtensionsModel\ProductPricesModel::model()->save($model[$i]);
                    }
                } else {
                    $pmodel[$i] = \ExtensionsModel\ProductPricesModel::model()->findByPk($_POST['ProductPrices']['id'][$i]);
                    $pmodel[$i]->unit_price = $_POST['ProductPrices']['unit_price'][$i];
                    $pmodel[$i]->discount = $_POST['ProductPrices']['discount'][$i];
                    $pmodel[$i]->period = $_POST['ProductPrices']['period'][$i];
                    $pmodel[$i]->updated_at = date("Y-m-d H:i:s");
                    if (!empty($pmodel[$i]->category_id) && $pmodel[$i]->unit_price > 0) {
                        try {
                            $update = \ExtensionsModel\ProductPricesModel::model()->update($pmodel[$i]);

                        } catch (\Exception $e) {
                            var_dump($e->getMessage()); exit;
                        }
                    }
                }
            }

            return $response->withJson(
                [
                    'status' => 'success',
                    'message' => 'Data berhasil disimpan.',
                ], 201);
        }

        return $this->_container->module->render(
            $response,
            'products/_price_form.html',
            [
                'show_delete_btn' => true,
                'model' => $model
            ]);
    }

    public function delete_price($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        // avoid double execution
        $current_time = time();
        if(isset($_SESSION['DeletePrices']) && !empty($_SESSION['DeletePrices'])) {
            $selisih = $current_time - $_SESSION['DeletePrices'];
            if ($selisih <= 10) {
                return $response->withJson(
                    [
                        'status' => 'success',
                        'message' => 'Data berhasil dihapus.',
                    ], 201);
            } else {
                $_SESSION['DeletePrices'] = $current_time;
            }
        } else {
            $_SESSION['DeletePrices'] = $current_time;
        }

        $model = \ExtensionsModel\ProductPricesModel::model()->findByPk($_POST['id']);
        $delete = \ExtensionsModel\ProductPricesModel::model()->delete($model);
        if ($delete) {
            return $response->withJson(
                [
                    'status' => 'success',
                    'message' => 'Data berhasil dihapus.',
                ], 201);
        }
    }
}