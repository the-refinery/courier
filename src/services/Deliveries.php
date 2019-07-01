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
use yii\base\Event;
use refinery\courier\records\Delivery as Delivery;
use refinery\courier\models\Delivery as DeliveryModel;
use refinery\courier\services\ModelPopulator;
use craft\db\Query;

/**
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 */

class Deliveries extends Component
{
	// Public Methods
	// =========================================================================

	/**
	 * @param array|\CDbCriteria $criteria
	 *
	 * @return Delivery[]
	 */
	public function getAllDeliveries($criteria = null)
	{
    // CONVERSION: $records = Courier_DeliveryRecord::model()
		// 	->with('blueprint')
		// 	->findAll($criteria);
		if(is_null($criteria))
		{
			$criteria = Delivery::find();
		}

		// $records = Delivery::find($criteria)
		$records = $criteria
			->with('blueprint')
			->all();

		// CONVERSION: $models = Courier_DeliveryModel::populateModels($records);
		// $models = $this->populateModels($records);
		$models = Courier::getInstance()
			->modelPopulator
			->populateModels(
				$records,
				\refinery\courier\models\Delivery::class
			);

		return $models;
		// return $records;
	}
/*
    private function populateModels(array $records): array
    {
        $models = [];

        if (!empty($records)) {
					foreach($records as $record) {
						$model = new DeliveryModel();
						$modelAttributes = array_keys($model->getAttributes());
						// var_dump($modelAttributes);
						// die();

						$recordAttributes = $record->getAttributes($modelAttributes);
						$model->setAttributes($recordAttributes);
            $models[] = $model;
					}
            // foreach ($records as $record) {
						// 		$recordAttributes = $record->getAttributes();
            //     // $model = new DeliveryModel();
						// 		// var_dump($model->getAttributes());
						// 		// die();
            //     $model = new DeliveryModel();
            //     $model->setAttributes($recordAttributes);

            //     $models[] = $model;
						// }
        }

        return $models;
		}
*/

	/**
	 * Create and save a delivery to record and keep track of a blueprint email that was sent
	 *
	 * @param  \CEvent $event
	 *
	 * @return void
	 */
	public function createDelivery(Event $event)
	{
		// $params = $event->params;
		$recipients = '';
		$blueprint = $event->blueprint;
		// var_dump($blueprint->toEmail);
		// die();

		// Do we have an Email model?
		// if (isset($params['email'])) {
		// 	$toEmail = $params['email']['toEmail'];
		// 	$recipients = is_array($toEmail) ? $this->_convertEmailArrayToString($toEmail) : $toEmail;
		// }

		if(isset($blueprint->toEmail)) {
			$toEmail = $blueprint->toEmail;
			$recipients = is_array($toEmail) ? $this->_convertEmailArrayToString($toEmail) : $toEmail;
		}

		/*
		$delivery = new Courier_DeliveryModel();
		$delivery->blueprintId 	 = $params['blueprint']->id;
		$delivery->toEmail 		 = $recipients;
		$delivery->success 		 = isset($params['success']) ? $params['success'] : false;
		$delivery->errorMessages = isset($params['error']) ? $params['error'] : '';
		*/

		$delivery = new DeliveryModel();
		$delivery->blueprintId = $blueprint->id;
		$delivery->toEmail 		 = $recipients;
		$delivery->success 		 = $event->success;
		$delivery->errorMessages = $event->errorMessage;

		// Save the delivery record
		$delivery->id = $this->_saveDelivery($delivery);

		$this->enforceDeliveriesLimit();
	}

	/**
	 * @param int $id
	 */
	public function deleteDeliveryById($id)
	{
    $record = Delivery::findOne($id);
    $result = false;

    if ($record) {
      $result = $record->delete();
    }

    return $result;
	}

	/**
	 * @param array|\CDbCriteria $criteria
	 *
	 * @return bool $result
	 *
	 * @throws \Exception
	 */
	public function deleteAllDeliveries($criteria = [])
	{
		return (bool) Courier_DeliveryRecord::model()->deleteAll($criteria);
	}

	/**
	 * Ensure and enforce that we never have more Courier_DeliveryModel Records saved to the DB than Courier's set delivery record limit
	 *
	 * @param int $deliveriesLimit
	 *
	 * @return void
	 */
	public function enforceDeliveriesLimit($deliveriesLimit = null)
	{
		if (!$deliveriesLimit) {
			// $deliveriesLimit = craft()->plugins->getPlugin('courier')->getSettings()->deliveriesRecordLimit;
			$deliveriesLimit = Courier::getInstance()
				->settings
				->deliveriesRecordLimit;
		}

    $deliveriesCount = (int) (new Query())
			->select('count(*)')
			->from(Delivery::tableName())
			->scalar();

		// $deliveriesCount = count(Courier_DeliveryRecord::model()->findAll());

		// Proceed only if limit was reached
		if (!($deliveriesCount > $deliveriesLimit)) {
			return;
		}

		// Create a custom query in which to execute a DELETE FROM. Turns out
		// Yii2 does not allow order/limit conditions on a deleteAll() command.
		$deliveriesTable = Craft::$app
			->getDb()
			->getSchema()
			->getRawTableName(Delivery::tableName());

		$deleteLimit = $deliveriesCount - $deliveriesLimit;

		// There is very little risk of the tableName undergoing an SQL injection attack,
		// so string injection for the table name should be sufficient.
		$dbCommand = Craft::$app
			->getDb()
			->createCommand("DELETE FROM {$deliveriesTable} ORDER BY dateCreated ASC limit :deleteLimit");

		$dbCommand->bindParam(':deleteLimit', $deleteLimit);
		$dbCommand->execute();

	}

	// Private Methods
	// =========================================================================

	/**
	 * @param  Courier_DeliveryModel $deliveryModel
	 *
	 * @return int|null $deliveryId
	 */
	private function _saveDelivery(DeliveryModel $deliveryModel)
	{
		if (!$deliveryModel->validate()) {
			// Validation errors to string
			$errors = array_column($deliveryModel->getErrors(), 0);
			$errors = implode(' ', $errors);
			// Log here
			// $error = Craft::t('Could not create delivery for blueprint “{blueprint}”. Errors: “{errors}”', [
			// 	'blueprint' => $event->blueprint->name,
			// 	'errors' => $errors,
			// ]);
			// Craft::error($error, LogLevel::Error);

			return null;
		}

		$deliveryRecord = new Delivery();

		$deliveryRecord->blueprintId  	= $deliveryModel->blueprintId;
		$deliveryRecord->toEmail 				= $deliveryModel->toEmail;
		$deliveryRecord->success 				= $deliveryModel->success;
		$deliveryRecord->errorMessages	= $deliveryModel->errorMessages;

		// Save the record to the DB
		$deliveryRecord->save(false);

		return $deliveryRecord->id;
	}


	/**
	 * When expecting the Mailer classes's toEmail array format of
	 * [ $email => $name, $email => $name, $email ]
	 * convert these with comma separation to a string in the format of
	 * $name ($email), $name ($email), $email
	 *
	 * @param  array  $emails
	 *
	 * @return string $emailsString
	 */
	private function _convertEmailArrayToString(array $emails)
	{
		$emailsString = '';
		$i = 0;

		foreach ($emails as $key => $val) {
			// Choose correct format depending on whether key is a string or sequentially indexed
			$withName = is_string($key);
			$emailsString .= $withName ?  $val . ' <' . $key . '>' : $val;
			if ($i < count($emails) - 1 && $withName) {
				$emailsString .= ', ';
			}
			$i++;
		}

		return $emailsString;
	}
}
