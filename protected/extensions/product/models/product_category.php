<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class ProductCategoryModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_product_category';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['title', 'required'],
        ];
    }

    public function getItem($data) {
        if (empty($data['id'])) {
            return false;
        }

        $model = self::model()->findByPk($data['id']);
        if ($model instanceof \RedBeanPHP\OODBBean) {
            return $model;
        }

        return false;
    }

    public function getItems($data = array()) {
        $sql = "SELECT i.*   
        FROM {tablePrefix}ext_product_category i 
        WHERE 1";

        $params = [];

        $sql .= " ORDER BY i.id ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }
}
