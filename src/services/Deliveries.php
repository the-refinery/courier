<?php

namespace refinery\courier\services;

use refinery\courier\Courier;

use Craft;
use craft\base\Component;
use yii\base\Event;
use refinery\courier\records\Delivery as Delivery;
use refinery\courier\models\Delivery as DeliveryModel;
use refinery\courier\services\ModelPopulator;
use craft\db\Query;

class Deliveries extends Component
{
	public function getAllDeliveries($criteria = null)
	{
		if(is_null($criteria))
		{
			$criteria = Delivery::find();
		}

		$records = $criteria
			->with('blueprint')
			->all();

		$models = Courier::getInstance()
			->modelPopulator
			->populateModels(
				$records,
				\refinery\courier\models\Delivery::class
			);

		return $models;
	}

	public function createDelivery(Event $event)
	{
		$recipients = '';
		$blueprint = $event->blueprint;

		if(isset($blueprint->toEmail)) {
			$toEmail = $blueprint->toEmail;
      $recipients = is_array($toEmail) ?
        $this->_convertEmailArrayToString($toEmail) :
        $toEmail;
		}

		$delivery = new DeliveryModel();
		$delivery->blueprintId = $blueprint->id;
		$delivery->toEmail 		 = $recipients;
		$delivery->success 		 = $event->success;
		$delivery->errorMessages = $event->errorMessage;

		// Save the delivery record
		$delivery->id = $this->_saveDelivery($delivery);

		$this->enforceDeliveriesLimit();
	}

	public function deleteDeliveryById($id)
	{
    $record = Delivery::findOne($id);
    $result = false;

    if ($record) {
      $result = $record->delete();
    }

    return $result;
	}

	public function deleteAllDeliveries($criteria = [])
	{
		$result = null;

		try {
			$result = Delivery::deleteAll();
    } catch(\Exception $e) {
			// Log here
		}

		return $result;
	}

	public function enforceDeliveriesLimit($deliveriesLimit = null)
	{
		if (!$deliveriesLimit) {
			$deliveriesLimit = Courier::getInstance()
				->settings
				->deliveriesRecordLimit;
		}

    $deliveriesCount = (int) (new Query())
			->select('count(*)')
			->from(Delivery::tableName())
			->scalar();

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

    // There is very little risk of the tableName undergoing an SQL injection
    // attack, so string injection for the table name should be sufficient.
		$dbCommand = Craft::$app
			->getDb()
			->createCommand("DELETE FROM {$deliveriesTable} ORDER BY dateCreated ASC limit :deleteLimit");

		$dbCommand->bindParam(':deleteLimit', $deleteLimit);
		$dbCommand->execute();
	}

	private function _saveDelivery(DeliveryModel $deliveryModel)
	{
		if (!$deliveryModel->validate()) {
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
      // Choose correct format depending on whether key is a string or
      // sequentially indexed
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
