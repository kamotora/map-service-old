<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User;

class RegisterForm extends Model
{
	public $username;
	public $password;
	public $passwordRepeat;
	public $full_name;
	public $email;
	public $verifyCode;
	
	public function rules()
	{
		return [
			['username', 'required', 'message' => 'Имя пользователя не может быть пустым'],
			['username', 'unique', 'targetClass' => User::className(), 'message' => 'Такое имя пользователя уже занято'],
			['username', 'trim'],
			['username', 'string', 'min' => 2, 'max' => 255],
			['full_name', 'required', 'message' => 'Имя не может быть пустым'],
			['email', 'required', 'message' => 'Пожалуйста, укажите e-mail'],
			['email', 'trim'],
			['email', 'email', 'message' => 'Некорректный адрес e-mail'],
			['email', 'unique', 'targetClass' => User::className(), 'message' => 'Такой адрес e-mail уже зарегистрирован'],
			['email', 'string', 'max' => 255],
			['password', 'required', 'message' => 'Пароль не может быть пустым'],
			['password', 'string', 'min' => 6, 'message' => 'Минимальная длина пароля - 6 символов'],
			['passwordRepeat', 'required', 'message' => 'Пожалуйста, повторите пароль'],
			['passwordRepeat', 'compare', 'compareAttribute' => 'password', 'skipOnEmpty' => false, 'message' => 'Пожалуйста, повторите пароль'],
			['verifyCode', 'captcha'],
		];
	}
	
	public function attributeLabels()
	{
		return [
			'verifyCode' => 'Код подтверждения',
		];
	}

	public function register()
	{
		if (!$this->validate())
		{
			return null;
		}

		$user = new User();
		$user->username = $this->username;
		$user->email = $this->email;
		$user->full_name = $this->full_name;
		$user->user_role = 1;
		$user->setPassword($this->password);
		$user->generateAuthKey();
		return $user->save() ? $user : null;
	}
}