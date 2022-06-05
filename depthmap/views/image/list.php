<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ImageUploadForm */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\bootstrap\Html;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Button;
use yii\bootstrap\Modal;
use yii\bootstrap\ActiveForm;

$this->title = 'Изображения';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="image-list">
    <p>
    <?php 
        if (!Yii::$app->user->isGuest)
        {
            $form = ActiveForm::begin([
                'id' => 'image-upload-form',
                'options' => [],
            ]);
            $footer = Html::tag('p', '', [
                'id' => 'image-upload-wait'
            ]);
            $footer .= Html::submitButton('OK', [
                'class' => 'form-group btn btn-primary', 
                'name' => 'upload-button',
                'onClick' => 'document.getElementById("image-upload-wait").innerHTML = "Пожалуйста, подождите..."'
                ]);
            $footer .= Html::a('Отмена', ['#'], ['class' => 'btn btn-default', 'data-dismiss' => 'modal']);
            Modal::begin([
                'header' => '<h3>Загрузка изображений</h3>',
                'toggleButton' => [
                    'label' => Html::icon('upload') . Html::encode('Загрузить изображения'),
                    'tag' => 'a',
                    'class' => 'btn btn-primary'
                ],
                'footer' => $footer
            ]);
            echo Html::tag('p', Html::encode('Поддерживаемые форматы: JPG, PNG, BMP, GIF'));
            echo $form->field($model, 'files[]')->fileInput(['multiple' => true, 'accept' => 'image/*'])->label('');
            Modal::end();
            ActiveForm::end();
        }
    ?>
    </p>
    <div class="container">
        <?php Pjax::begin(); ?>
        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView' => 'imageitem',
            'layout' => "{pager}\n{summary}\n<div class=\"row d-inline-flex\">{items}</div>\n{pager}",
            'options' => [
                'class' => 'd-inline-flex'
            ],
            'itemOptions' => [
                'class' => ''
            ]
        ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>