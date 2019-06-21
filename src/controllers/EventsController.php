<?php

namespace refinery\courier\controllers;
use refinery\courier\Courier;
use craft\web\Controller;
use Craft;
use yii\base\Exception;
use yii\web\Response;
use refinery\courier\records\Event as CourierEventRecord;
use refinery\courier\models\Event as CourierEventModel;
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

  public function actionCreate(): Response
  {
    $variables['title'] = 'Create new Courier Event';
    $variables['event'] = new CourierEventModel();
    return $this->renderTemplate('courier/_event', $variables);
  }

  public function actionSave(): Response
  {
    $this->requirePostRequest();

    $eventModel = new CourierEventModel();
    $request = Craft::$app->getRequest();

    $eventModel->id 					= $request->getParam('eventId', $eventModel->id);
    $eventModel->eventHandle  = $request->getParam('eventHandle', $eventModel->eventHandle);
    $eventModel->eventClass 	= $request->getParam('eventClass', $eventModel->eventClass);
    $eventModel->description  = $request->getParam('eventDescription', $eventModel->description);
    $eventModel->enabled      = $request->getParam('enabled', $eventModel->enabled);

    if (!Courier::getInstance()->events->saveEvent($eventModel)) {
      Craft::$app->getSession()->setError(Craft::t('courier', 'Couldn’t save blueprint.'));

      Craft::$app->getUrlManager()->setRouteParams([
        'event' => $eventModel,
      ]);

      return null;
    }

    Craft::$app->getSession()->setNotice(Craft::t('courier', 'Event saved.'));

    return $this->redirect("courier/events");
  }
}