<?php
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use app\models\User;
use yii\bootstrap\Modal;
?>
<div class="imageitem">
    <div class="panel panel-default">
        
        <div class="panel-body row d-inline-flex">
            <div class="container col-6 col-sm-4 col-md-3 p-2">
            <?= Html::a(Html::img('/img/thumb/' . $model->image_path, [
                'class' => 'img-thumbnail',
            ]), [
                'image/show', 
                'id' => $model->id
            ]) ?>
            </div>
            <div class="container-fluid">
            <h2><?= Html::encode($model->sheet_code) ?></h2>

            <p> <?= Html::encode('Загрузил: ' . User::findIdentity($model->uploaded_by)->getFullName())?></p>
            <p>Широта: <?= HtmlPurifier::process($model->north) ?> - <?= HtmlPurifier::process($model->south) ?></p>
            <p>Долгота: <?= HtmlPurifier::process($model->west) ?> - <?= HtmlPurifier::process($model->east) ?></p>
            </div>
        </div>
        <div class="panel-footer" id="image-control">
            <?= Html::a(
                'Детали', 
                [
                    'image/show', 
                    'id' => $model->id
                ],
                [
                    'class' => 'btn btn-primary'
                ]
                ) ?>
            <?php 
            if (!Yii::$app->user->isGuest)
            {
                if (Yii::$app->user->identity->id == $model->uploaded_by || Yii::$app->user->identity->user_role != 1)
                {
                    echo Html::a('Изменить', ['image/edit', 'id' => $model->id], [
                        'class' => 'btn btn-primary'
                    ]);
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
            } ?>
        </div>
    </div>
</div>