<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class ProductModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_product';
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

    public function getImages($data)
    {
        $sql = "SELECT i.*  
        FROM {tablePrefix}ext_product_images i 
        WHERE i.product_id =:product_id";

        $params = [ 'product_id' => $data['id'] ];

        if (isset($data['type'])) {
            $sql .= " AND i.type =:type";
            $params['type'] = $data['type'];
        }

        $sql .= " ORDER BY i.id ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );
        return $rows;
    }

    public function getPeriods()
    {
        return [
            '6H' => '6 Jam',
            '12H' => '12 Jam',
            '18H' => '18 Jam',
            '24H' => '24 Jam',
            ];
    }
}
