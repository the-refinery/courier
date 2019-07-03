<?php
namespace refinery\courier\services;

use refinery\courier\Courier;
use Craft;
use craft\base\Component;
use yii\base\Event;
use refinery\courier\records\Event as CourierEventRecord;
use refinery\courier\models\Event as CourierEventModel;
use refinery\courier\services\ModelPopulator;
use yii\log\Logger;

class Events extends Component
{
  public function saveEvent(CourierEventModel $eventModel)
  {
    if ($eventModel->id){
      $record = CourierEventRecord::findOne($eventModel->id);
    } else {
      $record = new CourierEventRecord();
    }

    $eventModel->validate();

    if($eventModel->hasErrors()) {
      return false;
    }

    $record->eventHandle  = $eventModel->eventHandle;
    $record->eventClass   = $eventModel->eventClass;
    $record->description  = $eventModel->description;
    $record->enabled      = $eventModel->enabled;

    $transaction = Craft::$app->db->beginTransaction();

    try {
      // Ensure that the event class exists. If it doesn't, this will throw
      // an exception. It should not be saved unless the class can be found.
      new $record->eventClass;

      $record->save(false);
      $transaction->commit();
    } catch(\Throwable $e) {
      $transaction->rollBack();
      Courier::log(
        "\nThere was a problem saving Event:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    $eventModel->id = $record->id;

    return true;
  }

  public function getAllEvents($criteria = null)
  {
    if(is_null($criteria))
    {
      $criteria = CourierEventRecord::find();
    }

    $records = [];

    try {
      $records = $criteria
        ->all();
    } catch(\Throwable $e) {
      Courier::log(
        "\nThere was a problem getting all Events:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    $models = Courier::getInstance()
      ->modelPopulator
      ->populateModels(
        $records,
        \refinery\courier\models\Event::class
      );

    return $models;
  }

  public function getEventById($id)
  {
    $record = null;
    try {
      $record = CourierEventRecord::findOne($id);
    } catch(\Throwable $e) {
      Courier::log(
        "\nThere was a problem getting Event by id={$id}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
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
        \refinery\courier\models\Event::class
      );

    return $models[0];
  }

  public function deleteEventById($id)
  {
    $transaction = Craft::$app->db->beginTransaction();

    try {
      $record = CourierEventRecord::findOne($id);
      $result = false;

      if ($record) {
        $result = $record->delete();
        $transaction->commit();
      } else {
        return false;
      }
    } catch(\Throwable $e) {
      $transaction->rollBack();
      Courier::log(
        "\nThere was a problem deleting Event id={$id}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    return true;
  }

public function setupEventListeners()
{
  try {
    $enabledBlueprints = Courier::getInstance()
      ->blueprints
      ->getEnabledBlueprints();

    $blueprintEventMap = Courier::getInstance()
      ->events
      ->eventIdMappingFromBlueprints($enabledBlueprints);

    foreach($enabledBlueprints as $blueprint) {
      if(!$blueprint->eventTriggers) {
        continue;
      }

      foreach($blueprint->eventTriggersJsonArray() as $eventTriggerId) {
        $event = $blueprintEventMap[$eventTriggerId] ?? null;

        if($event && $event->enabled) {
          $className = $event->eventClass;
          $obj = new $className;

          // Use reflection to attempt to get handle value
          $class_reflex = new \ReflectionClass($obj);
          $class_constants = $class_reflex->getConstants();
          if (array_key_exists($event->eventHandle, $class_constants)) {
            $constant_value = $class_constants[$event->eventHandle];
          } else {
            $constant_value = $event->eventHandle;
          }

          Event::on(
            get_class($obj),
            $constant_value,
            function(Event $event) use ($blueprint) {
              Courier::getInstance()
              ->blueprints
              ->checkEventConditions($event, $blueprint);
            }
          );
        }
      }
    }
  } catch(\Throwable $e) {
    Courier::log(
      "\nThere was a problem setting up Event Listeners:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
      Logger::LEVEL_ERROR
    );

    throw $e;
  }

  // On the event that an email is sent, create a successful delivery record
  Event::on(
    Emails::class,
    Emails::EVENT_AFTER_BLUEPRINT_EMAIL_SENT,
    function(Event $event){
    Courier::getInstance()
      ->deliveries
      ->createDelivery($event);
    }
  );

  // On the event that an email fails to send, create a failed delivery record
  Event::on(
    Emails::class,
    Emails::EVENT_AFTER_BLUEPRINT_EMAIL_FAILED,
    function(Event $event){
    Courier::getInstance()
      ->deliveries
      ->createDelivery($event);
    }
  );
}

  public function eventsFromBlueprints($blueprints)
  {
    $blueprintEventIds = [];

    // Gather up all the possible Courier Event IDs so that we can make one
    // database call to get them and use them in a lookup table below.
    foreach($blueprints as $blueprint) {
      if(!empty($blueprint->eventTriggers)) {
        foreach($blueprint->eventTriggersJsonArray() as $eventTriggerId) {
          array_push($blueprintEventIds, $eventTriggerId);
        }
      }
    }

    $blueprintEventIds = array_unique($blueprintEventIds);

    $events = [];

    try {
      // Get all Courier events by the IDs above
      $events = Courier::getInstance()
        ->events
        ->getAllEvents(
          CourierEventRecord::find()
            ->where(['id' => $blueprintEventIds])
        );
    } catch(\Throwable $e) {
      Courier::log(
        "\nThere was a problem getting Blueprints to set up mapping:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw $e;
    }

    return $events;
  }

  public function eventIdMappingFromBlueprints($blueprints)
  {
    $events = $this->eventsFromBlueprints($blueprints);

    // Create a lookup table (dictionary)
    // [event id] = event model
    $eventLookup = [];

    foreach($events as $event) {
      $eventLookup[$event->id] = $event;
    }

    return $eventLookup;
  }
}
