<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use Yii\db\CDbCriteria;

class ImageProblemModel extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%image_problem}}';
    }

    public function getImage()
    {
        return $this->hasOne(ImageModel::className(), ['id' => 'image_id']);
    }
}