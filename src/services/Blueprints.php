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

class Blueprints extends Component
{
	public function getAllBlueprints($criteria = null)
	{
		if(is_null($criteria))
		{
			$criteria = Blueprint::find();
		}

		$records = $criteria
			->all();

		$models = Courier::getInstance()
			->modelPopulator
			->populateModels(
				$records,
				\refinery\courier\models\Blueprint::class
			);

		return $models;
	}

	public function getEnabledBlueprints()
	{
		$criteria = Blueprint::find()
			->where(
				[
					'enabled' => true
				]
			);

		return $this->getAllBlueprints($criteria);
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
		$record->name 					= $model->name;
		$record->description 		= $model->description;
		$record->enabled 				= $model->enabled;
		$record->emailSubject 	= $model->emailSubject;
		$record->toEmail 				= $model->toEmail;
		$record->toName 				= $model->toName;
		$record->fromEmail 			= $model->fromEmail;
		$record->fromName 			= $model->fromName;
		$record->replyToEmail		= $model->replyToEmail;
		$record->ccEmail 				= $model->ccEmail;
		$record->bccEmail 			= $model->bccEmail;
		$record->eventTriggers	= $model->eventTriggers;
		$record->htmlEmailTemplatePath 	= $model->htmlEmailTemplatePath;
		$record->textEmailTemplatePath 	= $model->textEmailTemplatePath;
		$record->eventTriggerConditions = $model->eventTriggerConditions;

		$record->save(false);
		$model->id = $record->id;

		return true;
	}

	public function getBlueprintById($id)
	{
		$record = Blueprint::findOne($id);

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
    $record = Blueprint::findOne($id);
    $result = false;

    if ($record) {
      $result = $record->delete();
    }

    return $result;
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
		} catch (\Exception $e) {
			// Log here
			throw new \Exception($e);
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
		Craft::$app->queue->push($courierEmailJob);

		return true;
	}
}
