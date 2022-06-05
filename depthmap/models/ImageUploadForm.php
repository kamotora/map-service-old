<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\ImageModel;
use yii\db\Expression;
use yii\imagine\Image;
use Imagine\Image\Box;

class ImageUploadForm extends Model
{
    public $files;
    public $fileNames;

    public function rules()
    {
        return [
            [
                'files', 'image',
                'extensions' => ['jpg', 'jpeg', 'png', 'gif'],
                'skipOnEmpty' => false,
                'checkExtensionByMimeType' => true,
                'maxFiles' => 20
            ]
        ];
    }

    public function upload()
    {
        if ($this->validate())
        {
            $fileNames = array();
            foreach ($this->files as $file)
            {
                $newName = $this->randomFileName('');
                $file->saveAs(Yii::$app->basePath . '/web/img/orig/' . $newName . 'tmp.' . $file->extension);
                Image::getImagine()->open(Yii::$app->basePath . '/web/img/orig/' . $newName . 'tmp.' . $file->extension)->save(Yii::$app->basePath . '/web/img/orig/' . $newName . '.png', ['quality' => 90]);
                unlink(Yii::$app->basePath . '/web/img/orig/' . $newName . 'tmp.' . $file->extension);
                Image::getImagine()->open(Yii::$app->basePath . '/web/img/orig/' . $newName . '.png')->thumbnail(new Box(512, 512))->save(Yii::$app->basePath . '/web/img/thumb/' . $newName . '.png', ['quality' => 90]);
                Image::getImagine()->open(Yii::$app->basePath . '/web/img/orig/' . $newName . '.png')->thumbnail(new Box(1024, 1024))->save(Yii::$app->basePath . '/web/img/thumb-large/orig/' . $newName . '.png', ['quality' => 90]);
                $imageRecord = new ImageModel();
                        $imageRecord->image_path = $newName . '.png';
                        $imageRecord->uploaded_by = Yii::$app->user->identity->id;
                        $imageRecord->sheet_code = 'Неизвестно';
                        $imageRecord->point_nw_x = 0;
                        $imageRecord->point_nw_y = 0;
                        $imageRecord->point_ne_x = 0;
                        $imageRecord->point_ne_y = 0;
                        $imageRecord->point_sw_x = 0;
                        $imageRecord->point_sw_y = 0;
                        $imageRecord->point_se_x = 0;
                        $imageRecord->point_se_y = 0;
                        $imageRecord->north = '0 0 0';
                        $imageRecord->south = '0 0 0';
                        $imageRecord->west = '0 0 0';
                        $imageRecord->east = '0 0 0';
                        $imageRecord->status = 'raw';
                        $imageRecord->save();
            }
            return true;
        } else {
            return false;
        }
    }

    private function randomFileName($extension = false)
    {
        $extension = $extension ? '.' . $extension : '';
        do {
            $name = md5(microtime() . rand(0, 1000));
            $file = $name . $extension;
        } while (file_exists($file));
        return $file;
    }
}