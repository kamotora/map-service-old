<?php

namespace app\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\User;
use app\models\UserEditForm;

class UserController extends Controller 
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionEdit($id = null)
    {
        if (Yii::$app->user->isGuest || Yii::$app->user->identity->user_role != 3)
        {
            return $this->render('error', ['name' => 'Ошибка доступа', 'message' => 'У вас нет доступа к этой странице']);
        } else {
            $model = new UserEditForm();
            
            if ($model->load(Yii::$app->request->post()))
            {
                if ($model->validate())
                {
                    if ($model->save())
                    {
                        return $this->goBack();
                    }
                }
                
            }
            if ($id == null)
            {
                return $this->render('error', ['name' => 'Ошибка доступа', 'message' => 'Неверный запрос']);
            }
            $query = User::findOne($id);
            $model->id = $id;
            $model->full_name = $query->full_name;
            $model->email = $query->email;
            $model->user_role = $query->user_role;
            return $this->render('edit', ['model' => $model]);
        }
    }

    public function actionList()
    {
        if (Yii::$app->user->isGuest || Yii::$app->user->identity->user_role != 3)
        {
            return $this->render('error', ['name' => 'Ошибка доступа', 'message' => 'У вас нет доступа к этой странице']);
        } else {
            $query = User::find();
            $provider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 20,
                ],
                'sort' => [
                    'defaultOrder' => [
                        'full_name' => SORT_ASC
                    ],
                ],
            ]);
            Yii::$app->user->setReturnUrl(Yii::$app->request->url);
            return $this->render('list', ['dataProvider' => $provider]);
        }
    }
}