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
// use refinery\courier\records\Blueprint as Blueprint;
// use refinery\courier\models\Blueprint as BlueprintModel;


/**
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 */
class ModelPopulator extends Component
{
	public function populateModels(array $records, $targetModelClass): array
	{
		$models = [];

		if (!empty($records)) {
			foreach($records as $record) {
				$model = new $targetModelClass();
				$modelAttributes = array_keys($model->getAttributes());
				$recordAttributes = $record->getAttributes($modelAttributes);
				$model->setAttributes($recordAttributes);
				$models[] = $model;
			}
			/*
				foreach ($records as $record) {
						$recordAttributes = $record->getAttributes();

						// $model = new BlueprintModel();
						$model = new $targetModelClass();
						$model->setAttributes($recordAttributes, false);
						$models[] = $model;
				}
			*/
		}

		return $models;
	}
}