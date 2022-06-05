<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\UserEditForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Редактирование сведений о пользователе';
$this->params['breadcrumbs'][] = ['label' => 'Пользователи', 'url' => ['list']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-edit">
    <?php $form = ActiveForm::begin([
		'id' => 'user-edit-form',
		'layout' => 'horizontal',
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ]
	]); ?>
    <?= $form->field($model, 'id')->hiddenInput()->label(false); ?>
    <?= $form->field($model, 'full_name')->textInput(['autofocus' => true])->label('Имя') ?>
    <?= $form->field($model, 'email')->textInput()->label('E-mail') ?>
    <?= $form->field($model, 'user_role')->dropDownList([
        1 => 'Пользователь',
        2 => 'Модератор',
        3 => 'Администратор'
    ])->label('Роль') ?>
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'user-submit-button']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>