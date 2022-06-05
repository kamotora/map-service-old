<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ImageEditForm */
/* @var $problemModel app\models\ImageProblemModel */

use yii\web\View;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\models\ImageEditForm;
use app\models\ImageProblemModel;
use yii\bootstrap\Button;

$this->title = 'Редактирование сведений об изображении';
$this->params['breadcrumbs'][] = ['label' => 'Изображения', 'url' => ['list']];
$this->params['breadcrumbs'][] = $this->title;

$btnCode = <<<JS
$('#btn-get-sheet-code').click(
    function () {
        var north = $("input[name='ImageEditForm[north]']").last().val();
        var south = $("input[name='ImageEditForm[south]']").last().val();
        var west = $("input[name='ImageEditForm[west]']").last().val();
        var east = $("input[name='ImageEditForm[east]']").last().val();
        $.ajax(
            {
                url: document.location.origin + '/ajax/util/get_sheet_code',
                method: 'GET',
                data: {
                    north: north,
                    south: south,
                    west: west,
                    east: east
                },
                success: function (data) {
                    if (data.sheetCode !== null) {
                        $('#display-sheet-code').text(data.sheetCode)
                    } else {
                        $('#display-sheet-code').text('Не удалось определить')
                    }
                },
                dataType: "json"
            }
        );
    }
);
JS;
$this->registerJs($btnCode, View::POS_READY);
?>
<div class="image-edit container">
    <?php $form = ActiveForm::begin([
		'id' => 'image-edit-form',
		'layout' => 'horizontal',
        'fieldConfig' => [
            'template' => "<div class=\"col-lg-2\">{label}</div>\n<div class=\"col-lg-2\">{input}</div>\n<div class=\"col-lg-3\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2 control-label'],
        ],
	]); ?>
    <?= $form->field($model, 'id')->hiddenInput()->label(false); ?>
    <?= $form->field($model, 'status')->hiddenInput()->label(false); ?>
    <?= $form->field($model, 'sheet_code')->textInput(['autofocus' => true])->label('Номенклатура') ?>
    <div class="row d-inline-flex vertical-center">
    <?=
        Html::button('Подобрать', ['class' => 'btn btn-default col-sm-2', 'id' => 'btn-get-sheet-code']);
    ?>
        <p id="display-sheet-code" class="coord-variant col-sm-2"></p>
    </div>
    <h4>Точки обрезки изображения</h4>
    <div class="row">
        <p class="col-lg-2">Северо-западная: </p>
        <div class="form-inline">
            <?= $form->field($model, 'point_nw_x')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('X')?>
            <?= $form->field($model, 'point_nw_y')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('Y')?>
        </div>
        <p class="col-lg-2">Северо-восточная: </p>
        <div class="form-inline">
            <?= $form->field($model, 'point_ne_x')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('X')?>
            <?= $form->field($model, 'point_ne_y')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('Y')?>
        </div>
        <p class="col-lg-2">Юго-западная: </p>
        <div class="form-inline">
            <?= $form->field($model, 'point_sw_x')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('X')?>
            <?= $form->field($model, 'point_sw_y')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('Y')?>
        </div>
        <p class="col-lg-2">Юго-восточная: </p>
        <div class="form-inline">
            <?= $form->field($model, 'point_se_x')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('X')?>
            <?= $form->field($model, 'point_se_y')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('Y')?>
        </div>
    </div>
    <h4>Координатная привязка</h4>
    <div class="row">
        <div class="row d-inline-flex">
            <?= $form->field($model, 'north')->textInput()->label('Северная граница')?>
            <?php
                if ($problemModel != null)
                {
                    if ($problemModel->north != null && $problemModel->north != '')
                    {
                        echo Html::tag('p', 'Варианты: ' . $problemModel->north, ['class' => 'coord-variant']);
                    }
                }
            ?>
        </div>
        <div class="row d-inline-flex">
            <?= $form->field($model, 'south')->textInput()->label('Южная граница')?>
            <?php
                if ($problemModel != null)
                {
                    if ($problemModel->south != null && $problemModel->south != '')
                    {
                        echo Html::tag('p', 'Варианты: ' . $problemModel->south, ['class' => 'coord-variant']);
                    }
                }
            ?>
        </div>
        <div class="row d-inline-flex">
            <?= $form->field($model, 'west')->textInput()->label('Западная граница')?>
            <?php
                if ($problemModel != null)
                {
                    if ($problemModel->west != null && $problemModel->west != '')
                    {
                        echo Html::tag('p', 'Варианты: ' . $problemModel->west, ['class' => 'coord-variant']);
                    }
                }
            ?>
        </div>
        <div class="row d-inline-flex">
            <?= $form->field($model, 'east')->textInput()->label('Восточная граница')?>
            <?php
                if ($problemModel != null)
                {
                    if ($problemModel->east != null && $problemModel->east != '')
                    {
                        echo Html::tag('p', 'Варианты: ' . $problemModel->east, ['class' => 'coord-variant']);
                    }
                }
            ?>
        </div>
    </div>
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'image-submit-button']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>