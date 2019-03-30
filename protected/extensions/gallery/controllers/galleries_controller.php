<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;
use PHPMailer\PHPMailer\Exception;

class GalleriesController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET', 'POST'], '/view', [$this, 'view']);
        $app->map(['GET', 'POST'], '/update/[{id}]', [$this, 'update']);
        $app->map(['POST'], '/delete/[{id}]', [$this, 'delete']);
        $app->map(['POST'], '/upload-images', [$this, 'get_upload_images']);
        $app->map(['POST'], '/delete-image/[{id}]', [$this, 'delete_image']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['view', 'update', 'delete', 'upload-images', 'delete-image'],
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

        $success = false; $message = null;
        if (isset($_POST['Gallery'])){
            $model = new \ExtensionsModel\GalleryCategoryModel();
            $model->title = $_POST['Gallery']['title'];
            $model->description = $_POST['Gallery']['description'];
            $model->created_at = date('Y-m-d H:i:s');
            $model->updated_at = date('Y-m-d H:i:s');
            $create = \ExtensionsModel\GalleryCategoryModel::model()->save(@$model);
            if ($create) {
                $model2 = \ExtensionsModel\GalleryCategoryModel::model()->findByPk($model->id);
                $nr = str_repeat('0',4-strlen($model->id));
                $model2->code = 'G-'. $nr . $model->id;
                $model2->updated_at = date('Y-m-d H:i:s');
                $update = \ExtensionsModel\GalleryCategoryModel::model()->update($model2);

                $message = 'Your gallery has been successfully created.';
                $success = true;
                $gallery_id = $model->id;
            }
        }

        $galleries = \ExtensionsModel\GalleryCategoryModel::model()->findAll();

        return $this->_container->module->render($response, 'galleries/view.html', [
            'galleries' => $galleries,
            'message' => ($message) ? $message : null,
            'success' => $success,
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

        $model = \ExtensionsModel\GalleryCategoryModel::model()->findByPk($args['id']);
        $items = \ExtensionsModel\GalleryModel::model()->findAllByAttributes(['category_id' => $model->id]);

        $success = false; $message = null;
        if (isset($_POST['Gallery'])){

        }

        return $this->_container->module->render($response, 'galleries/update.html', [
            'items' => $items,
            'message' => ($message) ? $message : null,
            'success' => $success,
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

        $model = \ExtensionsModel\GalleryCategoryModel::model()->findByPk($args['id']);
        $delete = \ExtensionsModel\GalleryCategoryModel::model()->delete($model);
        if ($delete) {
            $message = 'Your gallery has been successfully deleted.';
            echo true;
        }
    }

    public function get_upload_images($request, $response, $args)
    {
        if ($this->_user->isGuest()){
            return $response->withRedirect($this->_login_url);
        }

        if (isset($_POST['GalleryImages'])) {
            $path_info = pathinfo($_FILES['GalleryImages']['name']['file_name']);
            if (!in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                echo json_encode(['status'=>'failed','message'=>'Allowed file type are jpg, png']); exit;
                exit;
            }
            $model = new \ExtensionsModel\GalleryModel();
            $model->category_id = $_POST['GalleryImages']['category_id'];
            $model->title = $_POST['GalleryImages']['title'];
            $model->caption = $_POST['GalleryImages']['caption'];
            $model->url = $_POST['GalleryImages']['url'];
            $model->order = $_POST['GalleryImages']['order'];
            $upload_folder = 'uploads/images/galleries';
            $model->image = $upload_folder.'/'.time().'.'.$path_info['extension'];
            $model->created_at = date("Y-m-d H:i:s");
            $model->updated_at = date("Y-m-d H:i:s");
            $create = \ExtensionsModel\GalleryModel::model()->save(@$model);
            if ($create > 0) {
                move_uploaded_file($_FILES['GalleryImages']['tmp_name']['file_name'], $model->image);
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

        $model = \ExtensionsModel\GalleryModel::model()->findByPk($_POST['id']);
        $image_path = $model->image;
        $delete = \ExtensionsModel\GalleryModel::model()->delete($model);
        if ($delete) {
            if (file_exists($image_path))
                unlink($image_path);
            echo true;
        }
        exit;
    }
}