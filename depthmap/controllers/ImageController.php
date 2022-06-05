<?php

namespace app\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\ImageUploadForm;
use app\models\User;
use app\models\ImageModel;
use app\models\ImageSearchFormModel;
use app\models\ImageSetModel;
use app\models\ImageSetRecordModel;
use app\models\ImageProblemModel;
use app\models\ImageEditForm;
use yii\web\UploadedFile;

class ImageController extends Controller
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
            ]
        ];
    }

    public function actionDelete()
    {
        if (Yii::$app->user->isGuest)
        {
            return $this->render('error', ['name' => 'Ошибка доступа', 'message' => 'У вас нет прав для выполнения этого действия']);
        }
        $imageId = Yii::$app->request->post('id', null);
        if ($imageId != null)
        {
            $imageRecord = ImageModel::findOne($imageId);
            if (Yii::$app->user->identity->id != $imageRecord->uploaded_by && Yii::$app->user->identity->user_role == 1)
            {
                return $this->render('/site/error', ['name' => 'Ошибка доступа', 'message' => 'У вас нет прав для выполнения этого действия']);
            } else {
                if (file_exists(Yii::$app->basePath . '/web/img/orig/' . $imageRecord->image_path))
                {
                    unlink(Yii::$app->basePath . '/web/img/orig/' . $imageRecord->image_path);
                }
                if (file_exists(Yii::$app->basePath . '/web/img/thumb/' . $imageRecord->image_path))
                {
                    unlink(Yii::$app->basePath . '/web/img/thumb/' . $imageRecord->image_path);
                }
                if (file_exists(Yii::$app->basePath . '/web/img/cropped/' . $imageRecord->image_path))
                {
                    unlink(Yii::$app->basePath . '/web/img/cropped/' . $imageRecord->image_path);
                }
                $imageRecord->delete();
            }
        }
        return $this->redirect(['list']);
    }

    public function actionList()
    {
        $uploadForm = new ImageUploadForm();
        if (Yii::$app->request->isPost)
        {
            $uploadForm->files = UploadedFile::getInstances($uploadForm, 'files');
            if ($uploadForm->upload())
            {
                Yii::$app->session->setFlash('success', 'Изображения загружены');
                return $this->refresh();
            }
        }
        $query = ImageModel::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'sheet_code' => SORT_ASC
                ],
            ],
        ]);
        return $this->render('list', ['model' => $uploadForm, 'dataProvider' => $provider]);
    }

    public function actionShow($id = null)
    {
        if ($id == null)
        {
            return $this->render('error', ['name' => 'Ошибка доступа', 'message' => 'Неверный запрос']);
        }
        $query = ImageModel::findOne($id);
        if ($query == null)
        {
            return $this->render('error', ['name' => '#404 Не найдено', 'message' => 'Запрашиваемая страница не найдена']);
        }
        return $this->render('show', ['model' => $query]);
    }

    public function actionEdit($id = null)
    {
        $model = new ImageEditForm();
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
        $query = ImageModel::findOne($id);
        if ($query == null)
        {
            return $this->render('error', ['name' => '#404 Не найдено', 'message' => 'Запрашиваемая страница не найдена']);
        }
        $model->id = $id;
        $model->sheet_code = $query->sheet_code;
        $model->point_nw_x = $query->point_nw_x;
        $model->point_nw_y = $query->point_nw_y;
        $model->point_ne_x = $query->point_ne_x;
        $model->point_ne_y = $query->point_ne_y;
        $model->point_se_x = $query->point_se_x;
        $model->point_se_y = $query->point_se_y;
        $model->point_sw_x = $query->point_sw_x;
        $model->point_sw_y = $query->point_sw_y;
        $model->north = $query->north;
        $model->south = $query->south;
        $model->west = $query->west;
        $model->east = $query->east;
        $model->status = $query->status;
        $problem = ImageProblemModel::findOne(['image_id' => $id]);
        
        return $this->render('edit', ['model' => $model, 'problemModel' => $problem]);
    }

    public function actionSearch()
    {
        $model = new ImageSearchFormModel();
        if ($model->load(Yii::$app->request->get()))
        {
            $query = null;
            if ($model->searchMode == 0)
            {
                $query = ImageModel::find()->where($model->sheetInclude ? ['like', 'sheet_code', $model->sheetCode] : ['=', 'sheet_code', $model->sheetCode]);
            } else if ($model->searchMode == 1) {
                $query = ImageModel::find()->where([
                    'and', 
                    'parse_dms(`north`) <= parse_dms(\'' . $model->north . '\')', 
                    'parse_dms(`north`) >= parse_dms(\'' . $model->south . '\')',
                    'parse_dms(`south`) <= parse_dms(\'' . $model->north . '\')', 
                    'parse_dms(`south`) >= parse_dms(\'' . $model->south . '\')',
                    'parse_dms(`west`) <= parse_dms(\'' . $model->east . '\')', 
                    'parse_dms(`west`) >= parse_dms(\'' . $model->west . '\')',
                    'parse_dms(`east`) <= parse_dms(\'' . $model->east . '\')', 
                    'parse_dms(`east`) >= parse_dms(\'' . $model->west . '\')',
                    ]);
            }
            if ($query != null)
            {
                $provider = new ActiveDataProvider([
                    'query' => $query,
                    'pagination' => [
                        'pageSize' => 20,
                    ],
                    'sort' => [
                        'defaultOrder' => [
                            'sheet_code' => SORT_ASC
                        ]
                    ]
                ]);
                return $this->render('search', ['model' => $model, 'searchResults' => $provider]);
            }
        }
        $model->searchMode = 0;
        return $this->render('search', ['model' => $model, 'searchResults' => null]);
    }

    public function actionSets()
    {
        $query = ImageSetModel::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'name' => SORT_ASC
                ]
            ]
        ]);
        return $this->render('sets', ['dataProvider' => $provider]);
    }
}