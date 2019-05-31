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
	public function getAllBlueprints($criteria = [])
	{
        // CONVERSION: $records = Courier_BlueprintRecord::model()->findAll($criteria);
        $records = Blueprint::findAll($criteria);

        // CONVERSION: $models = Courier_BlueprintModel::populateModels($records);
        $models = $this->populateModels($records);

        return $models;

        // return []; // TEMPORARY REMOVE ME
    }

    private function populateModels(array $records): array
    {
        $models = [];

        if (!empty($records)) {
            foreach ($records as $record) {
                $recordAttributes = $record->getAttributes();
                $model = new BlueprintModel();
                $model->setAttributes($recordAttributes);

                $models[] = $model;
            }
        }

        return $models;
    }
}
