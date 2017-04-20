<?php

namespace app\controllers;

use app\components\NullUser;
use app\models\forms\SaasuSettingsForm;
use app\models\forms\TimeSheetSettingsForm;
use app\models\forms\TogglSettingsForm;
use Yii;
use yii\filters\auth\HttpBasicAuth;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Controller;
use yii2mod\settings\actions\SettingsAction;

/**
 * Site controller.
 */
class SiteController extends Controller
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Yii::$app->user->enableSession = false;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        if ($this->action->id != 'error') {
            $behaviors['basicAuth'] = [
                'class' => HttpBasicAuth::className(),
                'auth' => function ($username, $password) {
                    if ($username == 'admin' && $password == getenv('APP_PASSWORD')) {
                        return new NullUser();
                    }
                    return null;
                },
            ];
        }
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'timesheet-settings' => [
                'class' => SettingsAction::class,
                'view' => 'timesheet-settings',
                'modelClass' => TimeSheetSettingsForm::class,
            ],
            'toggl-settings' => [
                'class' => SettingsAction::class,
                'view' => 'toggl-settings',
                'modelClass' => TogglSettingsForm::class,
            ],
            'saasu-settings' => [
                'class' => SettingsAction::class,
                'view' => 'saasu-settings',
                'modelClass' => SaasuSettingsForm::class,
            ],
        ];
    }

    /**
     * Renders the start page.
     *
     * @return string
     */
    public function actionIndex()
    {
        $toggl = Json::decode(Yii::$app->settings->get('app', 'toggl'));
        $times = Yii::$app->timeSheet->getTimes($toggl);
        $staffTimes = Yii::$app->timeSheet->getStaffTimes($times);
        $totals = Yii::$app->timeSheet->getTotals($times);
        return $this->render('index', [
            'toggl' => $toggl,
            'times' => $times,
            'staffTimes' => $staffTimes,
            'totals' => $totals,
        ]);
    }

    /**
     * Imports data from Toggl
     *
     * @return \yii\web\Response
     */
    public function actionImportToggl()
    {
        Yii::$app->settings->set('app', 'toggl', Json::encode(Yii::$app->toggl->import(Yii::$app->timeSheet->staff)));
        return $this->redirect(Url::home());
    }

    /**
     * Exports data to Saasu
     *
     * @return \yii\web\Response
     */
    public function actionExportSaasu()
    {
        // create sale invoices
        $toggl = Json::decode(Yii::$app->settings->get('app', 'toggl'));
        $times = Yii::$app->timeSheet->getTimes($toggl);
        foreach ($times as $pid => $_times) {
            //Yii::$app->saasu->createSaleInvoice($pid, $_times);
        }
        // create purchase invoices
        $staffTimes = Yii::$app->timeSheet->getStaffTimes($times);
        //debug($times);
        debug($staffTimes);
        die;
        foreach ($staffTimes as $sid => $_times) {
            //Yii::$app->saasu->createPurchaseInvoice($sid, $_times);
        }

        Yii::$app->settings->set('TogglSettingsForm', 'startDate', date('Y-m-d'));
        return $this->redirect(['/site/import-toggl']);
    }

    /**
     * Dumps the variables.
     *
     * @return string
     */
    public function actionDump()
    {
        $toggl = Json::decode(Yii::$app->settings->get('app', 'toggl'));
        $times = Yii::$app->timeSheet->getTimes($toggl);
        $staffTimes = Yii::$app->timeSheet->getStaffTimes($times);
        $totals = Yii::$app->timeSheet->getTotals($times);
        return $this->render('dump', [
            'toggl' => $toggl,
            'times' => $times,
            'staffTimes' => $staffTimes,
            'totals' => $totals,
        ]);
    }
}
