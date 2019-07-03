<?php

namespace refinery\courier\controllers;
use refinery\courier\Courier;
use craft\web\Controller;
use Craft;
use yii\base\Exception;
use yii\web\Response;
use refinery\courier\records\Event as CourierEventRecord;
use refinery\courier\models\Event as CourierEventModel;

class EventsController extends Controller
{
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
      Craft::$app
        ->getSession()
        ->setError(
          Craft::t(
            'courier',
            'Couldn’t save event.'
          )
        );

      Craft::$app->getUrlManager()->setRouteParams([
        'event' => $eventModel,
      ]);

      return $this->renderTemplate('courier/_event', [
        'event' => $eventModel,
      ]);
    }

    Craft::$app
      ->getSession()
      ->setNotice(
        Craft::t(
          'courier',
          'Event saved.'
        )
      );

    return $this->redirect("courier/events");
  }

  public function actionEdit() : Response
  {
    $variables = Craft::$app->getUrlManager()->getRouteParams([
      'variables'
    ]);

    // Get blueprint by id if it is not loaded already
    if (empty($variables['event'])) {
      $variables['event'] = Courier::getInstance()
        ->events
        ->getEventById($variables['id']);
    }

    if ($variables['event'] === null) {
      throw new HttpException(404);
    }

    $variables['title'] = "Edit Courier Event";

    return $this->renderTemplate('courier/_event', $variables);
  }

  public function actionDelete() : Response
	{
    $this->requirePostRequest();
    $this->requireAcceptsJson();

    $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

    $result = Courier::getInstance()
      ->events
      ->deleteEventById($id);

    return $this->asJson([
      'success' => $result
    ]);
  }
}
