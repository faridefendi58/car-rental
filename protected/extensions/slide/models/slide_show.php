<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class SlideShowModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_slide_show';
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

    public function getItems($data = array()) {
        $sql = "SELECT i.*   
        FROM {tablePrefix}ext_slide_show i 
        WHERE 1";

        $params = [];
        if (array_key_exists("category_id", array_keys($data))) {
            $params['category_id'] = $data['category_id'];
        }

        $sql .= " ORDER BY i.id ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql, $params );

        return $rows;
    }
}
