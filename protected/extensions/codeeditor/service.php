<?php
namespace Extensions;

class CodeEditorService
{
    protected $basePath;
    protected $themeName;
    protected $adminPath;
    protected $tablePrefix;

    public function __construct($settings = null)
    {
        $this->basePath = (is_object($settings))? $settings['basePath'] : $settings['settings']['basePath'];
        $this->themeName = (is_object($settings))? $settings['theme']['name'] : $settings['settings']['theme']['name'];
        $this->adminPath = (is_object($settings))? $settings['admin']['path'] : $settings['settings']['admin']['path'];
        $this->tablePrefix = (is_object($settings))? $settings['db']['tablePrefix'] : $settings['settings']['db']['tablePrefix'];
    }
    
    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function getMenu()
    {
        return [
            [ 'label' => 'Daftar File', 'url' => 'code-editor/view', 'icon' => 'fa fa-search' ],
        ];
    }
}
