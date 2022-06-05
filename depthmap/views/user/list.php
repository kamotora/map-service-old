<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;

$this->title = 'Управление пользователями';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-users">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            ['attribute' => 'full_name', 'label' => 'Имя'],
            ['attribute' => 'username', 'label' => 'Логин'],
            ['attribute' => 'email', 'label' => 'E-mail'],
            [
                'attribute' => 'user_role', 
                'label' => 'Роль', 
                'value' => function($data)
                {
                    if ($data->user_role === 1) {
                        return 'Пользователь';
                    } else if ($data->user_role === 2) {
                        return 'Модератор';
                    } else if ($data->user_role === 3) {
                        return 'Администратор';
                    }
                    return 'N/A';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => 'Действия',
                'template' => '{edit} {delete}',
                'buttons' => [
                    'edit' => function ($url,$model) {
                        return Html::a(
                        '<span class="glyphicon glyphicon-pencil"></span>', 
                        $url);
                    },
                ]
            ]
        ],
    ]); ?>
</div>