<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class ProductPricesModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_product_prices';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['product_id', 'required'],
            ['created_at', 'required', 'on'=>'create'],
        ];
    }

    public function getData($data = array()) {
        $sql = "SELECT t.*, c.title   
        FROM {tablePrefix}ext_product_prices t 
        LEFT JOIN {tablePrefix}ext_product_price_category c ON c.id = t.category_id 
        WHERE 1";

        $params = [];

        if (isset($data['product_id'])) {
            $sql .= ' AND t.product_id=:product_id';
            $params['product_id'] = $data['product_id'];
        }

        $sql .= " ORDER BY t.id ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        if (isset($data['grouped'])) {
            $periods = \ExtensionsModel\ProductModel::getPeriods();
            $items = [];
            foreach ($rows as $i => $row) {
                $items[$row['title']][$row['period']] = [
                    'period_name' => $periods[$row['period']],
                    'unit_price' => $row['unit_price'],
                    'discount' => $row['discount']
                ];
            }
            return $items;
        }

        return $rows;
    }
}
