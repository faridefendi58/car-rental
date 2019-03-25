<?php

namespace Extensions\Controllers;

use Components\BaseController as BaseController;
use PHPMailer\PHPMailer\Exception;

class CodeEditorController extends BaseController
{
    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET', 'POST'], '/view', [$this, 'view']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => ['view'],
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

        $tools = new \PanelAdmin\Components\AdminTools($this->_settings);
        $themes = $tools->getThemes();
        $files = [];
        foreach ($themes as $name => $theme) {
            $path = $theme['path'];
            $dir_contents = $this->getDirContents($path, '/\.(css|js|phtml)/');
            $dir_files = [];
            if (is_array($dir_contents) && count($dir_contents) > 0) {
                foreach ($dir_contents as $dir_content) {
                    $dir_files[basename($dir_content)] = ['name' => basename($dir_content), 'path' => $dir_content];
                }
            }
            $files[$name] = $dir_files;
        }

        $params = $request->getParams();
        if (!isset($params['theme'])) {
            $selected_theme = array_keys($files)[0];
        } else {
            $selected_theme = $params['theme'];
        }

        $selected_file = [];
        if (!isset($params['file'])) {
            $selected_file['name'] = array_keys($files[$selected_theme])[0];
        } else {
            $selected_file['name'] = $params['file'];
        }
        $selected_file['content'] = file_get_contents($files[$selected_theme][$selected_file['name']]['path']);
        $selected_file['type'] = pathinfo($selected_file['name'], PATHINFO_EXTENSION);

        $updated = false;
        if (isset($params['content'])) {
            try {
                file_put_contents($files[$selected_theme][$selected_file['name']]['path'], $params['content']);
                $updated = true;
            } catch (Exception $e) {}
        }

        return $this->_container->module->render($response, 'codeeditors/view.html', [
            'files' => $files,
            'selected_theme' => $selected_theme,
            'selected_file' => $selected_file,
            'updated' => $updated
        ]);
    }

    private function getDirContents($dir, $filter = '', &$results = array()) {
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);

            if(!is_dir($path)) {
                if(empty($filter) || preg_match($filter, $path)) $results[] = $path;
            } elseif($value != "." && $value != "..") {
                $this->getDirContents($path, $filter, $results);
            }
        }

        return $results;
    }
}