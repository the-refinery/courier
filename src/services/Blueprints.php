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
}
