<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User;

class UserEditForm extends Model
{
    public $id;
    public $full_name;
    public $email;
    public $user_role;

    public function rules()
	{
		return [
            ['id', 'integer'],
			['full_name', 'required', 'message' => 'Имя не может быть пустым'],
			['email', 'required', 'message' => 'Пожалуйста, укажите e-mail'],
			['email', 'trim'],
			['email', 'email', 'message' => 'Некорректный адрес e-mail'],
            ['email', 'string', 'max' => 255],
            ['user_role', 'integer']
		];
    }
    
    public function save()
    {
        $query = User::findIdentity($this->id);
        if ($query == null)
        {
            return false;
        }
        $query->full_name = $this->full_name;
        $query->email = $this->email;
        $query->user_role = $this->user_role;
        $query->save();
        return true;
    }
}