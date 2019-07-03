<?php

namespace refinery\courier\services;

use refinery\courier\Courier;
use Craft;
use craft\base\Component;

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
		}

		return $models;
	}
}
