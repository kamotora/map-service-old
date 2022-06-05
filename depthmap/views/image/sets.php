<?php
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $dataProvider yii\data\ActiveDataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use app\models\ImageSetModel;
use app\models\User;

$this->title = 'Наборы изображений';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="image-sets">
<?=
GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        ['attribute' => 'name', 'label' => 'Название'],
        [
            'attribute' => 'created_by',
            'label' => 'Создал',
            'value' => function($data)
            {
                //return var_dump($data->getUser());
                //return $data->getUser()->full_name;
                return User::findIdentity($data->created_by)->full_name;
            }
        ]
    ]
]);
?>
</div>