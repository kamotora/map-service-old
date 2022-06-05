<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\RegisterForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;

$this->title = 'Регистрация';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-register">
	<h1><?= Html::encode($this->title) ?></h1>
	<p>Пожалуйста, заполните следующие поля для входа на сайт:</p>
	<?php $form = ActiveForm::begin([
		'id' => 'register-form',
		'layout' => 'horizontal',
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
	]); ?>
	
		<?= $form->field($model, 'username')->textInput(['autofocus' => true])->label('Логин') ?>
		<?= $form->field($model, 'full_name')->textInput()->label('Полное имя') ?>
		<?= $form->field($model, 'email')->textInput()->label('E-mail') ?>
		<?= $form->field($model, 'password')->passwordInput()->label('Пароль') ?>
		<?= $form->field($model, 'passwordRepeat')->passwordInput()->label('Повторите пароль') ?>
		<?= $form->field($model, 'verifyCode')->widget(Captcha::className(), [
			
		]) ?>
		<div class="form-group">
			<div class="col-lg-offset-1 col-lg-11">
				<?= Html::submitButton('Зарегистрироваться', ['class' => 'btn btn-primary', 'name' => 'register-button']) ?>
			</div>
		</div>
	
	<?php ActiveForm::end(); ?>
</div>