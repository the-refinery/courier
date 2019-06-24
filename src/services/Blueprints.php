<?php
/**
 * Courier plugin for Craft CMS 3.x
 *
 * This is a CraftCMS 3 fork of the original Courier plugin. The original project can be found here: https://github.com/therefinerynz/courier
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2019 The Refinery
 */

namespace refinery\courier\services;

use refinery\courier\Courier;

use Craft;
use craft\base\Component;
use refinery\courier\records\Blueprint as Blueprint;
use refinery\courier\models\Blueprint as BlueprintModel;
use refinery\courier\services\ModelPopulator;
use yii\base\Event;

/**
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 */
class Blueprints extends Component
{
    // Public Methods
    // =========================================================================

	/**
	 * @param array|\CDbCriteria $criteria
	 *
	 * @return BlueprintModel[]
	 */
	public function getAllBlueprints($criteria = null)
	{
		if(is_null($criteria))
		{
			$criteria = Blueprint::find();
		}

		$records = $criteria
			->all();

		// CONVERSION: $models = Courier_DeliveryModel::populateModels($records);
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
			->where([
				'enabled' => true
			]);

			return $this->getAllBlueprints($criteria);
			// 50         $dataSourceRecord = DataSourceRecord::find()->where([
			// 	51             'id' => $dataSourceId
			// 	52         ])->one();
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

		// $record->validate();
		// $model->addErrors($record->getErrors());
		// // Fail validation if there were errors on the model or reocrd
		// if ($model->hasErrors()) {
		// 	return false;
		// }

		$record->save(false);
		$model->id = $record->id;

		return true;
	}

	/**
	 * @param int $id
	 *
	 * @return BlueprintModel|null
	 */
	public function getBlueprintById($id)
	{
		// $record = Courier_BlueprintRecord::model()->findById($id);

		// if (!$record) {
		// 	return null;
		// }

		// $model = Courier_BlueprintModel::populateModel($record);

		// return $model;

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

	public function checkEventConditions(Event $event, BlueprintModel $blueprint)
	{
		// var_dump($event->sender);
		// die();
		// $renderVariables = array_merge(compact('blueprint'), $event->params);
		// $globalSets = craft()->globals->getAllSets();


		$renderVariables = [];
		$renderVariables['event'] = $event;
		$renderVariables['sender'] = $event->sender;

		// $renderVariables = array_merge(compact('blueprint'), $event->params);
		$globalSets = \craft\elements\GlobalSet::find()
			->anyStatus()
			->all();

		foreach ($globalSets as $globalSet) {
			$renderVariables[$globalSet->handle] = $globalSet;
		}

		try {
			// $eventTriggerConditions = craft()->templates->renderString($blueprint->eventTriggerConditions, $renderVariables);
			$eventTriggerConditions = \Craft::$app
				->view
				->renderString($blueprint->eventTriggerConditions, $renderVariables);
		} catch (\Exception $e) {
			// Log here

			throw new Exception($e);
		}

		$eventTriggerConditions = trim($eventTriggerConditions);

		// If the trigger condition yields something other than "1" or "true", return
		// since the trigger is not valid in this case.
		if($eventTriggerConditions !== "1" && $eventTriggerConditions !== "true") {
			return;
		}

		// var_dump("Event trigger successful");
		// die();
		// Send the email here:
		// craft()->courier_emails->sendBlueprintEmail($blueprint, $renderVariables);
		return;



		/*
		// Prep render variables
		$renderVariables = array_merge(compact('blueprint'), $event->params);
		$globalSets = craft()->globals->getAllSets();

		foreach ($globalSets as $globalSet) {
			$renderVariables[$globalSet->handle] = $globalSet;
		}

		try {
			// Render the string with Twig
			$eventTriggerConditions = craft()->templates->renderString($blueprint->eventTriggerConditions, $renderVariables);
		}
		// Template parse error
		catch (\Exception $e) {
			$errorMessage = $e->getMessage();
			$error = Craft::t("Template parse error encountered while parsing field “Event Trigger Conditions” for the blueprint named “{blueprint}”:\r\n{error}", [
				'blueprint' => $blueprint->name,
				'error' => $errorMessage
			]);
			CourierPlugin::log($error, LogLevel::Error, true);

			throw new Exception($error);
		}

		// Event trigger conditions were not met
		if (trim($eventTriggerConditions) !== 'true') {
			return;
		}

		// If everything looks all good, send the email
		craft()->courier_emails->sendBlueprintEmail($blueprint, $renderVariables);
		*/
	}
}
