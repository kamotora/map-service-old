<?php

namespace app\models;

use Yii;
use yii\base\Model;

class ImageSearchFormModel extends Model
{
    public $searchMode;
    public $sheetCode;
    public $sheetInclude = true;
    public $north, $south, $west, $east;

    public function rules()
    {
        return [
            ['searchMode', 'safe'],
            ['sheetInclude', 'safe'],
            [['sheetCode', 'north', 'south', 'west', 'east'], 'safe']
        ];
    }
}
