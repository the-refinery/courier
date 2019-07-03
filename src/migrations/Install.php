<?php

namespace refinery\courier\migrations;

use craft\db\Migration;
use refinery\courier\records\Event as CourierEventRecord;

class Install extends Migration
{
  private $blueprintsTable = '{{%courier_blueprints}}';
  private $deliveriesTable = '{{%courier_deliveries}}';
  private $eventsTable = '{{%courier_events}}';

  public function safeUp()
  {
    $this->createTables();
  }

  public function safeDown()
  {
    $this->dropTables();
  }

  public function createTables()
  {
    $blueprintsTableCheck = $this->getDb()->tableExists($this->blueprintsTable);

    if ($blueprintsTableCheck == false) {
      // Original mysql table structure for Craft2 version.
      // ----------------------------------------------------------------------------------------
      // | Field                  | Type                | Null | Key | Default | Extra          |
      // +------------------------+---------------------+------+-----+---------+----------------+
      // | id                     | int(11)             | NO   | PRI | NULL    | auto_increment |
      // | name                   | varchar(255)        | NO   | UNI | NULL    |                |
      // | fromName               | varchar(255)        | YES  |     |         |                |
      // | htmlEmailTemplatePath  | varchar(510)        | NO   |     | NULL    |                |
      // | toEmail                | varchar(510)        | NO   |     | NULL    |                |
      // | fromEmail              | varchar(255)        | NO   |     | NULL    |                |
      // | emailSubject           | varchar(510)        | NO   |     | NULL    |                |
      // | toName                 | varchar(255)        | YES  |     |         |                |
      // | replyToEmail           | varchar(255)        | YES  |     |         |                |
      // | ccEmail                | varchar(510)        | YES  |     |         |                |
      // | bccEmail               | varchar(510)        | YES  |     |         |                |
      // | textEmailTemplatePath  | varchar(510)        | YES  |     |         |                |
      // | description            | varchar(1020)       | YES  |     |         |                |
      // | eventTriggerConditions | varchar(1020)       | NO   |     | false   |                |
      // | eventTriggers          | text                | YES  |     | NULL    |                |
      // | enabled                | tinyint(1) unsigned | NO   |     | 1       |                |
      // | dateCreated            | datetime            | NO   |     | NULL    |                |
      // | dateUpdated            | datetime            | NO   |     | NULL    |                |
      // | uid                    | char(36)            | NO   |     | 0       |                |
      // +------------------------+---------------------+------+-----+---------+----------------+

      $this->createTable($this->blueprintsTable,
        [
          'id' => $this->primaryKey(),
          'name' => $this->string(255)->notNull(),
          'fromName' => $this->string(255)->null(),
          'htmlEmailTemplatePath' => $this->string(510)->notNull(),
          'toEmail' => $this->string(510)->notNull(),
          'fromEmail' => $this->string(255)->notNull(),
          'emailSubject' => $this->string(510)->notNull(),
          'toName' => $this->string(255)->null(),
          'replyToEmail' => $this->string(255)->null(),
          'ccEmail' => $this->string(510)->null(),
          'bccEmail' => $this->string(510)->null(),
          'textEmailTemplatePath' => $this->string(510)->null(),
          'description' => $this->string(1020)->null(),
          'eventTriggerConditions' => $this->string(1020)->defaultValue("false")->notNull(),
          'eventTriggers' => $this->text()->null(),
          'enabled' => $this->boolean()->defaultValue(true)->notNull(),
          'dateCreated' => $this->dateTime()->notNull(),
          'dateUpdated' => $this->dateTime()->notNull(),
          'uid' => $this->uid(),
        ]
      );

      // Create the unique index for blueprints name field
      $this->createIndex(
        $this->db->getIndexName($this->blueprintsTable, 'name', true, true),
        $this->blueprintsTable,
        'name',
        true
      );
    }

    $deliveriesTableCheck = $this->getDb()->tableExists($this->deliveriesTable);

    if ($deliveriesTableCheck == false) {
      // Original mysql table structure
      // +---------------+---------------------+------+-----+---------+----------------+
      // | Field         | Type                | Null | Key | Default | Extra          |
      // +---------------+---------------------+------+-----+---------+----------------+
      // | id            | int(11)             | NO   | PRI | NULL    | auto_increment |
      // | blueprintId   | int(10)             | NO   | MUL | NULL    |                |
      // | toEmail       | varchar(255)        | YES  |     |         |                |
      // | errorMessages | varchar(1020)       | YES  |     |         |                |
      // | success       | tinyint(1) unsigned | NO   |     | 0       |                |
      // | dateCreated   | datetime            | NO   |     | NULL    |                |
      // | dateUpdated   | datetime            | NO   |     | NULL    |                |
      // | uid           | char(36)            | NO   |     | 0       |                |
      // +---------------+---------------------+------+-----+---------+----------------+
      $this->createTable($this->deliveriesTable,
        [
          'id' => $this->primaryKey(),
          'blueprintId' => $this->integer()->notNull(),
          'toEmail' => $this->string(255)->null(),
          'errorMessages' => $this->string(255)->null(),
          'success' => $this->boolean()->defaultValue(false)->notNull(),
          'dateCreated' => $this->dateTime()->notNull(),
          'dateUpdated' => $this->dateTime()->notNull(),
          'uid' => $this->uid(),
        ]
      );

      // Add the foreign key from deliveries -> blueprints
      $this->addForeignKey(
        null,
        $this->deliveriesTable,
        ['blueprintId'],
        $this->blueprintsTable,
        ['id'],
        'CASCADE'
      );
    }

    $eventsTableCheck = $this->getDb()->tableExists($this->eventsTable);

    if ($eventsTableCheck == false) {
      $this->createTable($this->eventsTable,
        [
          'id' => $this->primaryKey(),
          'eventClass' => $this->text()->notNull(),
          'eventHandle' => $this->text()->notNull(),
          'description' => $this->text()->null(),
          'enabled' => $this->boolean()->defaultValue(true)->notNull(),
          'dateCreated' => $this->dateTime()->notNull(),
          'dateUpdated' => $this->dateTime()->notNull(),
          'uid' => $this->uid(),
        ]
      );
    }

    // Create some out-of-the-box events to use.
    $entryBeforeSaveEvent = new CourierEventRecord();
    $entryBeforeSaveEvent->eventHandle  = 'EVENT_BEFORE_SAVE';
    $entryBeforeSaveEvent->eventClass   = '\craft\elements\Entry';
    $entryBeforeSaveEvent->description  = 'This event fires before an entry is saved.';
    $entryBeforeSaveEvent->enabled      = true;
    $entryBeforeSaveEvent->save(false);

    $entryAfterSaveEvent = new CourierEventRecord();
    $entryAfterSaveEvent->eventHandle  = 'EVENT_AFTER_SAVE';
    $entryAfterSaveEvent->eventClass   = '\craft\elements\Entry';
    $entryAfterSaveEvent->description  = 'This event fires after an entry is saved.';
    $entryAfterSaveEvent->enabled      = true;
    $entryAfterSaveEvent->save(false);
  }

  public function dropTables()
  {
    $deliveriesTableCheck = $this->getDb()->tableExists($this->deliveriesTable);

    if ($deliveriesTableCheck) {
      $this->dropTable($this->deliveriesTable);
    }

    $blueprintsTableCheck = $this->getDb()->tableExists($this->blueprintsTable);

    if ($blueprintsTableCheck) {
      $this->dropTable($this->blueprintsTable);
    }

    $eventsTableCheck = $this->getDb()->tableExists($this->eventsTable);

    if ($eventsTableCheck) {
      $this->dropTable($this->eventsTable);
    }
  }
}
