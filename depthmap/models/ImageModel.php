<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use Yii\db\CDbCriteria;
use yii\behaviors\TimestampBehavior;

/**
 * Image model
 * @property integer $id
 * 
 */
class ImageModel extends ActiveRecord
{
    public $orig_w, $orig_h;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%images}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'uploaded_by']);
    }
}

?>