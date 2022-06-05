<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use Yii\db\CDbCriteria;

class ImageSetRecordModel extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%image_set_record}}';
    }

    public function getSet()
    {
        return $this->hasOne(ImageSetModel::className(), ['id' => 'set_id']);
    }

    public function getImage()
    {
        return $this->hasOne(ImageModel::className(), ['id' => 'image_id']);
    }
}