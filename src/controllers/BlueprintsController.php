<?php

namespace refinery\courier\controllers;

use refinery\courier\Courier;
use craft\web\Controller;
use Craft;
use yii\base\Exception;
use yii\web\Response;
use refinery\courier\records\Blueprint;
use refinery\courier\models\Blueprint as BlueprintModel;
use refinery\courier\records\Event as CourierEventRecord;

class BlueprintsController extends Controller
{
  public function init()
  {
    $this->requireAdmin();
  }

  public function actionIndex(): Response
  {
    $variables = [];

    $blueprints = Courier::getInstance()
      ->blueprints
      ->getAllBlueprints();

    $eventLookup = Courier::getInstance()
      ->events
      ->eventIdMappingFromBlueprints($blueprints);

    $variables["blueprints"] = $blueprints;
    $variables["eventLookup"] = $eventLookup;

    return $this->renderTemplate('courier/blueprints', $variables);
  }

  public function actionCreate(): Response
  {
    $variables['title'] = 'Create new blueprint';
    $variables['blueprint'] = new Blueprint();
    $variables['availableEvents'] = $this->buildAvailableEventsCheckboxOptions(
      Courier::getInstance()
        ->events
        ->getAllEvents()
    );
    return $this->renderTemplate('courier/_blueprint', $variables);
  }

  private function buildAvailableEventsCheckboxOptions($availableEvents)
  {
    $options = [];

    foreach($availableEvents as $availableEvent)
    {
      $option = array(
        "label" => "Class: <b>{$availableEvent["eventClass"]}</b>, Handle: <b>{$availableEvent["eventHandle"]}</b>",
        "value" => $availableEvent->id
      );
      array_push($options, $option);
    }

    return $options;
  }

  public function actionSave(): Response
  {
    $this->requirePostRequest();

    $blueprint = new BlueprintModel();
    $request = Craft::$app->getRequest();

    $blueprint->id 						= $request->getParam('blueprintId', $blueprint->id);
    $blueprint->name 					= $request->getParam('name', $blueprint->name);
    $blueprint->description 	= $request->getParam('description', $blueprint->description);
    $blueprint->enabled 			= $request->getParam('enabled', $blueprint->enabled);
    $blueprint->emailSubject 	= $request->getParam('emailSubject', $blueprint->emailSubject);
    $blueprint->toEmail 			= $request->getParam('toEmail', $blueprint->toEmail);
    $blueprint->toName 				= $request->getParam('toName', $blueprint->toName);
    $blueprint->fromEmail 		= $request->getParam('fromEmail', $blueprint->fromEmail);
    $blueprint->fromName 			= $request->getParam('fromName', $blueprint->fromName);
    $blueprint->replyToEmail	= $request->getParam('replyToEmail', $blueprint->replyToEmail);
    $blueprint->ccEmail 			= $request->getParam('ccEmail', $blueprint->ccEmail);
    $blueprint->bccEmail 			= $request->getParam('bccEmail', $blueprint->bccEmail);
    $blueprint->eventTriggers = $request->getParam('eventTriggers', $blueprint->eventTriggers);
    $blueprint->htmlEmailTemplatePath 	= $request->getParam('htmlEmailTemplatePath', $blueprint->htmlEmailTemplatePath);
    $blueprint->textEmailTemplatePath 	= $request->getParam('textEmailTemplatePath', $blueprint->textEmailTemplatePath);
    $blueprint->eventTriggerConditions 	= $request->getParam('eventTriggerConditions', $blueprint->eventTriggerConditions);

    if (!Courier::getInstance()->blueprints->saveBlueprint($blueprint)) {
      Craft::$app
        ->getSession()
        ->setError(
          Craft::t('courier', 'Couldn’t save blueprint.')
        );

      $availableEvents = $this->buildAvailableEventsCheckboxOptions(
        Courier::getInstance()
          ->events
          ->getAllEvents()
      );

      Craft::$app->getUrlManager()->setRouteParams([
        'blueprint' => $blueprint,
        'availableEvents' => $availableEvents
      ]);

      return $this->renderTemplate('courier/_blueprint', [
        'blueprint' => $blueprint,
        'availableEvents' => $availableEvents
      ]);
    }

    Craft::$app
      ->getSession()
      ->setNotice(
        Craft::t('courier', 'Blueprint saved.')
      );

    return $this->redirect("courier/blueprints");
  }

  public function actionEdit() : Response
  {
    $variables = Craft::$app
      ->getUrlManager()
      ->getRouteParams([
        'variables'
      ]);

    $variables['availableEvents'] = $this->buildAvailableEventsCheckboxOptions(
      Courier::getInstance()
        ->events
        ->getAllEvents()
    );

    // Get blueprint by id if it is not loaded already
    if (empty($variables['blueprint'])) {
      $variables['blueprint'] = Courier::getInstance()
        ->blueprints
        ->getBlueprintById($variables['id']);
    }

    // Could not find requested Blueprint
    if ($variables['blueprint'] === null) {
      throw new HttpException(404);
    }

    $variables['title'] = $variables['blueprint']->name;

    return $this->renderTemplate('courier/_blueprint', $variables);
  }

  public function actionDelete()
  {
    $this->requirePostRequest();
    $request = Craft::$app->getRequest();
    $id = $request->getRequiredBodyParam('id');

    $result = Courier::getInstance()
      ->blueprints
      ->deleteBlueprintById($id);

    return $this->asJson([
      'success' => $result
    ]);
  }
}
