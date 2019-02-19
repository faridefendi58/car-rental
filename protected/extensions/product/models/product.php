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

    public function getItems($data = array()) {
        $sql = "SELECT i.*, CONCAT(im.upload_folder, '/', im.file_name) AS image, 
        p.unit_price, p.discount, c.title AS price_title, ct.slug AS category_slug   
        FROM {tablePrefix}ext_product i  
        LEFT JOIN {tablePrefix}ext_product_category ct ON ct.id = i.category_id 
        LEFT JOIN {tablePrefix}ext_product_images im ON im.product_id = i.id 
        LEFT JOIN {tablePrefix}ext_product_prices p ON p.product_id = i.id 
        LEFT JOIN {tablePrefix}ext_product_price_category c ON c.id = p.category_id 
        WHERE 1";

        $params = [];
        if (isset($data['category_id'])) {
            $sql .= ' AND i.category_id =:category_id';
            $params['category_id'] = $data['category_id'];
        }

        $sql .= " GROUP BY i.id ORDER BY i.id ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }
}
