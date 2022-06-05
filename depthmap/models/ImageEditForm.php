<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\ImageModel;

class ImageEditForm extends Model
{
    public $id;
    public $sheet_code;
    public $point_nw_x, $point_nw_y;
    public $point_ne_x, $point_ne_y;
    public $point_se_x, $point_se_y;
    public $point_sw_x, $point_sw_y;
    public $north, $south, $west, $east;
    public $status;

    public function rules()
    {
        return [
            [
                ['id', 'sheet_code', 'status',
                'point_nw_x', 'point_nw_y',
                'point_ne_x', 'point_ne_y',
                'point_se_x', 'point_se_y',
                'point_sw_x', 'point_sw_y',
                'north', 'south', 'west', 'east'], 'required'
            ],
            ['north', 'match', 'pattern' => '/(?:\d+°(?:\d+\'(?:\d+\")?)?|\d+(?: \d+(?: \d+)?)?)/'],
            ['south', 'match', 'pattern' => '/(?:\d+°(?:\d+\'(?:\d+\")?)?|\d+(?: \d+(?: \d+)?)?)/'],
            ['west', 'match', 'pattern' => '/(?:\d+°(?:\d+\'(?:\d+\")?)?|\d+(?: \d+(?: \d+)?)?)/'],
            ['east', 'match', 'pattern' => '/(?:\d+°(?:\d+\'(?:\d+\")?)?|\d+(?: \d+(?: \d+)?)?)/'],
        ];
    }

    public function save()
    {
        $query = ImageModel::findOne($this->id);
        if ($query == null)
        {
            return false;
        }
        $query->north = $this->north;
        $query->south = $this->south;
        $query->west = $this->west;
        $query->east = $this->east;
        if ($query->point_nw_x != $this->point_nw_x || $query->point_nw_y != $this->point_nw_y
        || $query->point_ne_x != $this->point_ne_x || $query->point_ne_y != $this->point_ne_y
        || $query->point_se_x != $this->point_se_x || $query->point_se_y != $this->point_se_y
        || $query->point_sw_x != $this->point_sw_x || $query->point_sw_y != $this->point_sw_y)
        {
            $query->point_nw_x = $this->point_nw_x;
            $query->point_nw_y = $this->point_nw_y;
            $query->point_ne_x = $this->point_ne_x;
            $query->point_ne_y = $this->point_ne_y;
            $query->point_se_x = $this->point_se_x;
            $query->point_se_y = $this->point_se_y;
            $query->point_sw_x = $this->point_sw_x;
            $query->point_sw_y = $this->point_sw_y;
            $query->status = 'recrop';
        }
        $query->save;
        return true;
    }
}