<?php
namespace Extensions;

class GalleryService
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
        $sql = "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_gallery` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(128) DEFAULT NULL,
          `image` varchar(255) NOT NULL,
          `caption` text,
          `url` text NOT NULL,
          `order` int(11) NOT NULL,
          `category_id` int(11) DEFAULT '0',
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

        $sql .= "CREATE TABLE IF NOT EXISTS `{tablePrefix}ext_gallery_category` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `code` varchar(64) DEFAULT NULL,
          `title` varchar(128) NOT NULL,
          `description` text,
          `configs` text,
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
        
        INSERT INTO `{tablePrefix}ext_gallery_category` (`id`, `title`, `description`, `configs`, `created_at`, `updated_at`) VALUES
        ('', 'Default', NULL, NULL, '{dateNow}', '{dateNow}');
        COMMIT;";

        $sql = str_replace(['{tablePrefix}', '{dateNow}'], [$this->tablePrefix, date("Y-m-d H:i:s")], $sql);
        
        $model = new \Model\OptionsModel();
        $install = $model->installExt($sql);

        return $install;
    }

    public function uninstall()
    {
        return true;
    }
}
