<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\ImageModel */

use yii\bootstrap\Html;
use yii\helpers\HtmlPurifier;
use yii\widgets\ListView;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Button;
use yii\bootstrap\ButtonGroup;
use yii\bootstrap\Modal;
use app\models\User;
use yii\bootstrap\ActiveForm;

$this->title = 'Просмотр изображения';
$this->params['breadcrumbs'][] = ['label' => 'Изображения', 'url' => ['list']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="image-show">
    <h1><?= Html::encode($model->sheet_code) ?></h1>
    <?php 
    if ($model->status == 'croperror' || $model->status == 'detecterror')
    {
        Modal::begin([
            'header' => '<h3>Имеются проблемы</h3>',
            'toggleButton' => [
                'label' => 'Имеются проблемы',
                'tag' => 'a',
                'class' => 'label label-danger'
            ]
        ]);
        if ($model->status == 'croperror')
        {
            echo Html::tag('p', 'Не удалось обрезать изображение. Вы можете исправить изображение или ввести координаты обрезки вручную.');
        } else if ($model->status == 'detecterror') {
            echo Html::tag('p', 'Не удалось распознать геокоординаты карты, либо они распознаны неоднозначно. Вы можете уточнить их вручную.');
        }
        Modal::end();
    } else if ($model->status == 'raw' || $model->status == 'recrop')
    {
        Modal::begin([
            'header' => '<h3>В обработке</h3>',
            'toggleButton' => [
                'label' => 'В обработке',
                'tag' => 'a',
                'class' => 'label label-warning'
            ]
        ]);
        if ($model->status == 'raw') {
            echo Html::tag('p', 'Изображение недавно загружено и требует обработки. Пожалуйста, подождите.');
        } else if ($model->status == 'recrop') {
            echo Html::tag('p', 'Изображение поставлено в очередь повторной обрезки. Пожалуйста, подождите.');
        }
        Modal::end();
    } ?>
    <?php 
    echo ButtonGroup::widget([
        'options' => [
            'class' => ['widget' => 'btn-group']
        ],
        'buttons' => [
            ['label' => 'Оригинал'],
            ['label' => 'Обрезка', 'visible' => ($model->status == 'ready' || $model->status == 'recrop' || $model->status == 'detecterror')],
        ]
    ]);
    ?>

    <p><?= Html::img('/img/thumb-large/orig/' . $model->image_path, [
            'class' => 'img-thumbnail',

        ]) ?></p>
    <div class="panel panel-default">
        <div class="panel-header">
            <h3>Информация: </h3>
        </div>
        <div class="panel-body">
            <table class="table">
                <tr>
                    <td>Загрузил:</td><td><?= Html::encode(User::findIdentity($model->uploaded_by)->getFullName()) ?></td>
                </tr>
                <tr>
                    <td>Широта:</td><td><?= HtmlPurifier::process($model->west) ?> - <?= HtmlPurifier::process($model->east) ?></td>
                </tr>
                <tr>
                    <td>Долгота:</td><td><?= HtmlPurifier::process($model->north) ?> - <?= HtmlPurifier::process($model->south) ?></td>
                </tr>
                <tr>
                    <td>Состояние:</td><td><?php 
                    if ($model->status == 'raw')
                    {
                        echo Html::encode('Ожидает обработки');
                    } else if ($model->status == 'croperror') {
                        echo Html::encode('Ошибка обрезки');
                    } else if ($model->status == 'detecterror') {
                        echo Html::encode('Ошибка распознавания');
                    } else if ($model->status == 'recrop') {
                        echo Html::encode('Ожидает повторной обрезки');
                    } else if ($model->status == 'ready') {
                        echo Html::encode('Готово');
                    }
                    ?></td>
                </tr>
            </table>
        </div>
        <div class="panel-footer">
        <?php
        if (!Yii::$app->user->isGuest)
        {
            if (Yii::$app->user->identity->id == $model->uploaded_by || Yii::$app->user->identity->user_role != 1)
            {
                echo Html::a('Изменить', ['image/edit', 'id' => $model->id], ['class' => 'btn btn-primary']);
                $footer = Html::a(
                    'OK', 
                    ['image/delete'], 
                    [
                        'class' => 'btn btn-default', 
                        'data-dismiss' => 'modal', 
                        'data-method' => 'POST',
                        'data-params' => [
                            'id' => $model->id
                        ]
                    ]
                );
                $footer .= Html::a('Отмена', ['#'], ['class' => 'btn btn-default', 'data-dismiss' => 'modal']);
                    Modal::begin([
                    'header' => '<h3>Подтверждение</h3>',
                    'toggleButton' => [
                        'label' => 'Удалить',
                        'tag' => 'a',
                        'class' => 'btn btn-danger'
                    ],
                    'footer' => $footer
                ]);
                echo Html::tag('p', Html::encode('Вы действительно хотите удалить изображение?'));
                Modal::end();
            }
        }
        ?>
        </div>
    </div>
</div>