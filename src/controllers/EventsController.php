<?php

namespace refinery\courier\controllers;
use refinery\courier\Courier;
use craft\web\Controller;
use Craft;
use yii\base\Exception;
use yii\web\Response;
// use refinery\courier\records\Blueprint;
// use refinery\courier\models\Blueprint as BlueprintModel;

class EventsController extends Controller
{
  // Public Methods
  // =========================================================================

  /**
   * @inheritdoc
   */
  public function init()
  {
    $this->requireAdmin();
  }

  public function actionIndex(): Response
  {
    $variables = [];
    $events = Courier::getInstance()->events->getAllEvents();
    $variables["events"] = $events;
    return $this->renderTemplate('courier/events', $variables);
  }
}