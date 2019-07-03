<?php

namespace refinery\courier\services;

use refinery\courier\Courier;
use Craft;
use craft\base\Component;
use refinery\courier\records\Blueprint as Blueprint;
use refinery\courier\models\Blueprint as BlueprintModel;
use refinery\courier\services\ModelPopulator;
use refinery\courier\queue\jobs\SendCourierEmailJob;
use yii\base\Event;
use yii\log\Logger;

class Blueprints extends Component
{
  public function getAllBlueprints($criteria = null)
  {
    if(is_null($criteria))
    {
      $criteria = Blueprint::find();
    }

    $models = [];

    try {
      $records = $criteria
        ->all();

      $models = Courier::getInstance()
        ->modelPopulator
        ->populateModels(
          $records,
          \refinery\courier\models\Blueprint::class
        );
    } catch(\Throwable $e) {
      Courier::log(
        "\nThere was a problem obtaining all Blueprints:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    return $models;
  }

  public function getEnabledBlueprints()
  {
    try {
      $criteria = Blueprint::find()
        ->where(
          [
            'enabled' => true
          ]
        );

      return $this->getAllBlueprints($criteria);
    } catch(\Throwable $e) {
      Courier::log(
        "\nThere was a problem obtaining enabled Blueprints:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }
  }

  public function saveBlueprint(BlueprintModel $model)
  {
    if ($model->id)
      $record = Blueprint::findOne($model->id);
    else {
      $record = new Blueprint();
    }

    $model->validate();

    if ($model->hasErrors()) {
      return false;
    }

    // Populate the blueprint record
    $record->name           = $model->name;
    $record->description    = $model->description;
    $record->enabled        = $model->enabled;
    $record->emailSubject   = $model->emailSubject;
    $record->toEmail        = $model->toEmail;
    $record->toName         = $model->toName;
    $record->fromEmail      = $model->fromEmail;
    $record->fromName       = $model->fromName;
    $record->replyToEmail   = $model->replyToEmail;
    $record->ccEmail        = $model->ccEmail;
    $record->bccEmail       = $model->bccEmail;
    $record->eventTriggers  = $model->eventTriggers;
    $record->htmlEmailTemplatePath  = $model->htmlEmailTemplatePath;
    $record->textEmailTemplatePath  = $model->textEmailTemplatePath;
    $record->eventTriggerConditions = $model->eventTriggerConditions;

    $transaction = Craft::$app->db->beginTransaction();

    try {
      $record->save(false);
      $transaction->commit();
    } catch(\Throwable $e) {
      $transaction->rollBack();
      Courier::log(
        "\nThere was a problem saving Blueprint:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    $model->id = $record->id;

    return true;
  }

  public function getBlueprintById($id)
  {
    $record = null;

    try {
      $record = Blueprint::findOne($id);
    } catch(\Throwable $e) {
      Courier::log(
        "\nThere was a problem getting Blueprint by id={$id}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    if (!$record) {
      return null;
    }

    $models = Courier::getInstance()
      ->modelPopulator
      ->populateModels(
        [$record],
        \refinery\courier\models\Blueprint::class
      );

    return $models[0];
  }

  public function deleteBlueprintById($id)
  {
    $transaction = Craft::$app->db->beginTransaction();

    try {
      $record = Blueprint::findOne($id);

      if ($record) {
        $result = $record->delete();
        $transaction->commit();
      } else {
        throw new Exception("Blueprint with id={$id} not found to delete.");
      }
    } catch (\Throwable $e) {
      $transaction->rollBack();
      Courier::log(
        "\nThere was a problem deleting Blueprint={$id}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    return true;
  }

  public function checkEventConditions(Event $event, BlueprintModel $blueprint)
  {
    $renderVariables = [];
    $renderVariables['event'] = $event;
    $renderVariables['sender'] = $event->sender;

    $globalSets = \craft\elements\GlobalSet::find()
      ->anyStatus()
      ->all();

    foreach ($globalSets as $globalSet) {
      $renderVariables[$globalSet->handle] = $globalSet;
    }

    try {
      $eventTriggerConditions = \Craft::$app
      ->view
      ->renderString($blueprint->eventTriggerConditions, $renderVariables);
    } catch (\Throwable $e) {
      Courier::log(
        "\nThere was a problem rendering eventTriggerConditions for Blueprint id={$blueprint->id}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    $eventTriggerConditions = trim($eventTriggerConditions);

    // If the trigger condition yields something other than "1" or "true",
    // return since the trigger is not valid in this case.
    if($eventTriggerConditions !== "1" && $eventTriggerConditions !== "true") {
      return;
    }

    $courierEmailJob = new SendCourierEmailJob();
    $courierEmailJob->renderVariables = $renderVariables;
    $courierEmailJob->blueprintId = $blueprint->id;
    $courierEmailJob->blueprintName = $blueprint->name;

    // Send email job
    try {
      Craft::$app->queue->push($courierEmailJob);
    } catch(\Throwable $e) {
      Courier::log(
        "\nThere was a problem adding a Courier email job to the queue:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    return true;
  }
}
