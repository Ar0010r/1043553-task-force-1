<?php

namespace frontend\controllers;

use App\Task;
use frontend\components\task\actions\ActionAccomplish;
use frontend\components\task\actions\ActionCancel;
use frontend\components\task\actions\ActionRefuse;
use frontend\components\task\actions\ActionRespond;
use frontend\controllers\parentControllers\SecuredController;

class TaskActionController extends SecuredController
{
    public function actionRespond($taskId)
    {
        $actionRespond = new ActionRespond($taskId);

        return $actionRespond->processAction();
    }

    public function actionAccomplish($taskId)
    {
        $actionAccomplish = new ActionAccomplish($taskId);

        return $actionAccomplish->processAction();
    }

    public function actionRefuse($taskId)
    {
        $actionRefuse = new ActionRefuse($taskId);
        $actionRefuse->processAction();

        return $this->redirectBack();
    }

    public function actionCancel($taskId)
    {
        $actionCancel = new ActionCancel($taskId);
        $actionCancel->processAction();

        return $this->redirectBack();
    }
}
