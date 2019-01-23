<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;
use PHPMailer\PHPMailer\Exception;

class SlidesController extends BaseController
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

        $model = new \ExtensionsModel\SlideShowModel();
        $slides = \ExtensionsModel\SlideShowModel::model()->findAll();

        return $this->_container->module->render($response, 'slides/view.html', [
            'slides' => $slides
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

        $model = new \ExtensionsModel\SlideShowModel('create');
        $categories = \ExtensionsModel\SlideShowCategoryModel::model()->findAll();
        $slide_id = 0;

        $success = false; $errors = []; $data = [];
        if (isset($_POST['SlideShow'])){
            $data = $_POST['SlideShow'];
            $path_info = pathinfo($_FILES['SlideShow']['name']['image']);
            if (!in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                array_push($errors, 'Image file should be in jpg or png format.');
            }
            if (empty($_POST['SlideShow']['title'])) {
                array_push($errors, 'Title is required.');
            }
            if (empty($path_info)) {
                array_push($errors, 'Image is required.');
            }
            $upload_folder = 'uploads/images/slides';
            $file_name = time().'.'.$path_info['extension'];
            $model->title = $_POST['SlideShow']['title'];
            $model->image = $upload_folder .'/'. $file_name;
            $model->caption = $_POST['SlideShow']['caption'];
            $model->url = $_POST['SlideShow']['url'];
            if ($_POST['SlideShow']['order']) {
                $model->order = $_POST['SlideShow']['order'];
            } else {
                $model->order = 0;
            }
            $model->category_id = $_POST['SlideShow']['category_id'];
            //$model->description = $_POST['SlideShow']['description'];
            $model->created_at = date('Y-m-d H:i:s');
            $model->updated_at = date('Y-m-d H:i:s');
            if (count($errors) == 0) {
                $create = \ExtensionsModel\SlideShowModel::model()->save(@$model);
                if ($create > 0) {
                    try {
                        $uploadfile = $upload_folder . '/' . $file_name;
                        move_uploaded_file($_FILES['SlideShow']['tmp_name']['image'], $uploadfile);
                    } catch (Exception $e) {}

                    $message = 'Your slide has been successfully created.';
                    $success = true;
                    $slide_id = $model->id;
                } else {
                    $message = 'Failed to create new slide.';
                    $success = false;
                }
            } else {
                $success = false;
                $message = implode(", ", $errors);
            }
        }

        return $this->_container->module->render($response, 'slides/create.html', [
            'categories' => $categories,
            'message' => ($message) ? $message : null,
            'success' => $success,
            'slide_id' => $slide_id,
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

        $model = \ExtensionsModel\SlideShowModel::model()->findByPk($args['id']);
        $categories = \ExtensionsModel\SlideShowCategoryModel::model()->findAll();

        $success = false; $errors = []; $data = [];
        if (isset($_POST['SlideShow'])){
            $data = $_POST['SlideShow'];
            $old_image = $model->image;
            if (!empty($_FILES['SlideShow']['name']['image'])) {
                $path_info = pathinfo($_FILES['SlideShow']['name']['image']);
                if (!in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                    array_push($errors, 'Image file should be in jpg or png format.');
                }

                $upload_folder = 'uploads/images/slides';
                $file_name = time().'.'.$path_info['extension'];
                $model->image = $upload_folder .'/'. $file_name;
            }

            if (empty($_POST['SlideShow']['title'])) {
                array_push($errors, 'Title is required.');
            }

            $model->title = $_POST['SlideShow']['title'];
            $model->caption = $_POST['SlideShow']['caption'];
            $model->url = $_POST['SlideShow']['url'];
            if ($_POST['SlideShow']['order']) {
                $model->order = $_POST['SlideShow']['order'];
            } else {
                $model->order = 0;
            }
            $model->category_id = $_POST['SlideShow']['category_id'];
            //$model->description = $_POST['SlideShow']['description'];
            $model->updated_at = date('Y-m-d H:i:s');
            if (count($errors) == 0) {
                $update = \ExtensionsModel\SlideShowModel::model()->update($model);
                if ($update > 0) {
                    if (!empty($file_name)) {
                        try {
                            $uploadfile = $upload_folder . '/' . $file_name;
                            move_uploaded_file($_FILES['SlideShow']['tmp_name']['image'], $uploadfile);
                            unlink($old_image);
                        } catch (Exception $e) {}
                    }

                    $message = 'Your slide has been successfully updated.';
                    $success = true;
                    $slide_id = $model->id;
                } else {
                    $message = 'Failed to create new slide.';
                    $success = false;
                }
            } else {
                $success = false;
                $message = implode(", ", $errors);
            }
        }

        return $this->_container->module->render($response, 'slides/update.html', [
            'categories' => $categories,
            'message' => ($message) ? $message : null,
            'success' => $success,
            'slide_id' => $model->id,
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

        $model = \ExtensionsModel\SlideShowModel::model()->findByPk($args['id']);
        $image_file = $model->image;
        $delete = \ExtensionsModel\SlideShowModel::model()->delete($model);
        if ($delete) {
            if (!empty($image_file)) {
                try {
                    unlink($image_file);
                } catch (Exception $e) {}
            }
            $message = 'Your slide has been successfully deleted.';
            echo true;
        }
    }
}