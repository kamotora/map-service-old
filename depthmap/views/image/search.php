<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\model\ImageSearchFormModel */
/* @var $searchResults yii\data\ActiveDataProvider */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use yii\widgets\Pjax;
use yii\widgets\ListView;
use app\models\ImageSearchFormModel;
$this->title = 'Поиск карт';
$this->params['breadcrumbs'][] = $this->title;

$js = <<<JS
$(document).ready(
    function() {
        $('#panel-search-terms > .panel-heading:first').click(
            function() {

            }
        )
    }
);
JS;
$this->registerJs($js);

?>
<div class="image-search">
    <?php Pjax::begin(); ?>
    <?php
        $form = ActiveForm::begin([
            'id' => 'image-edit-form',
            'layout' => 'horizontal',
            'method' => 'GET',
            'action' => Url::to(['image/search']),

        ]);
    ?>
    <div class="panel panel-primary" id="panel-search-terms" >
        <div class="panel-heading">
            <p data-toggle="collapse" data-parent="#panel-search-terms" href="#panel-search-terms-collapse" class="panel-title">Параметры поиска</p>
        </div>
        <div id="panel-search-terms-collapse" class="panel-collapse collapse in">
            <div class="panel-body">
                <?= $form->field($model, 'searchMode')->radioList([
                    0 => 'По номенклатуре',
                    1 => 'По координатам'
                ])->label('Критерии'); ?>
                <hr/>
                <div class="form-inline">
                <?= $form->field($model, 'sheetCode')->textInput([
                    'template' => "<div class=\"col-sm-6\">{label}</div>\n<div class=\"col-xs-1\">{input}</div>"
                    ])->label('Номенклатура', ['class' => 'control-label col-sm-4']); ?>
                <?= $form->field($model, 'sheetInclude')->checkbox()->label('И входящие', ['class' => 'col-sm-4']); ?>
                </div>
                <hr/>
                <div class="form-inline">
                    <?= $form->field($model, 'north')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('Север', ['class' => 'control-label col-sm-4']); ?>
                    <?= $form->field($model, 'south')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('Юг', ['class' => 'control-label col-sm-4']); ?>
                    <?= $form->field($model, 'west')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('Запад', ['class' => 'control-label col-sm-4']); ?>
                    <?= $form->field($model, 'east')->textInput(['template' => "<div>{label}</div>\n<div class=\"col-xs-1\">{input}</div>"])->label('Восток', ['class' => 'control-label col-sm-4']); ?>
                </div>
                <hr/>
                <div class="form-group">
                    <div class="col-lg-offset-1 col-lg-11">
                        <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary', 'name' => 'image-submit-button']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
    <?php Pjax::end(); ?>
    <div class='image-search-results'>
    <?php Pjax::begin(); ?>
    <?php
        if ($searchResults != null)
        {
            echo ListView::widget([
                'dataProvider' => $searchResults,
                'itemView' => 'imageitem_small',
                'pager' => [
                    'linkOptions' => ['data-method' => 'post'],
                ],
                'layout' => "{pager}\n{summary}\n<div class=\"row d-inline-flex\">{items}</div>\n{pager}",
                'options' => [
                    'class' => 'd-inline-flex',
                ],
                'itemOptions' => [
                    'class' => ''
                ]
            ]);
        }
    ?>
    <?php Pjax::end(); ?>
    </div>
</div>