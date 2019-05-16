<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class ProductModel extends \Model\BaseModel
{
    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';

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
            '2D' => '2 Hari',
            '3D' => '3 Hari',
            '4D' => '4 Hari',
            '5D' => '5 Hari',
            '6D' => '6 Hari',
            '1W' => '1 Minggu',
            '2W' => '2 Minggu',
            '3W' => '3 Minggu',
            '1M' => '1 Bulan',
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

        if (isset($data['exceptions'])) {
            $sql .= ' AND i.id NOT IN ('. $data['exceptions'].')';
        }

        $sql .= " GROUP BY i.id ORDER BY i.id ASC";

        if (isset($data['limit'])) {
            $sql .= ' LIMIT '. $data['limit'];
        }

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }

    public function getSitemaps($data = [])
    {
        $sql = "SELECT t.id, t.slug AS product_slug, c.slug AS category_slug, 
        t.updated_at AS last_update  
        FROM {tablePrefix}ext_product t
        LEFT JOIN {tablePrefix}ext_product_category c ON c.id = t.category_id
        WHERE t.status =:status";

        $params = [ 'status' => self::STATUS_ENABLED ];

        $sql .= " ORDER BY t.created_at ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );
        $items = []; $check_items = []; $categories = [];
        if (count($rows) > 0) {
            $tool = new \Components\Tool();
            $url_origin = $tool->url_origin();
            foreach ($rows as $i => $row) {
                if (!in_array($row['category_slug'], $categories)) {
                    array_push($categories, $row['category_slug']);
                    $items[] = [
                        'loc' => $url_origin.'/'. $row['category_slug'],
                        'lastmod' => date("c", strtotime($row['last_update'])),
                        'priority' => 0.5
                    ];
                }

                if (!in_array($row['product_slug'], $check_items)) {
                    array_push($check_items, $row['product_slug']);
                    $items[] = [
                        'loc' => $url_origin.'/product/'. $row['category_slug'] .'/'. $row['product_slug'],
                        'lastmod' => date("c", strtotime($row['last_update'])),
                        'priority' => 0.5
                    ];
                }
            }
        }

        return $items;
    }
}
