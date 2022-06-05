<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use Yii\db\CDbCriteria;

class ImageSetModel extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%image_set}}';
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    public function getRecords()
    {
        return $this->hasMany(ImageSetRecordModel::className(), ['set_id' => 'id']);
    }
}