<?php
namespace ExtensionsModel;

require_once __DIR__ . '/../../../models/base.php';

class TranslationModel extends \Model\BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'ext_translation';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            ['original_text', 'required'],
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

    public function getItems() {
        $sql = "SELECT i.*   
        FROM {tablePrefix}ext_translation i 
        WHERE 1";

        $sql .= " GROUP BY i.original_text ORDER BY i.id ASC";

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $rows = \Model\R::getAll( $sql );

        return $rows;
    }

    public function findByOriginalText($data) {
        $sql = "SELECT i.translated_text  
        FROM {tablePrefix}ext_translation i 
        WHERE i.language_id =:language_id AND i.original_text =:original_text";

        $params = ['language_id' => $data['language_id'], 'original_text' => $data['original_text']];

        $sql = str_replace(['{tablePrefix}'], [$this->_tbl_prefix], $sql);

        $row = \Model\R::getRow( $sql, $params );

        return $row['translated_text'];
    }
}
