<?php

namespace refinery\courier\records;

use refinery\courier\Courier;

use Craft;
use craft\db\ActiveRecord;
use refinery\courier\records\Delivery;

class Blueprint extends ActiveRecord
{
  public static function tableName()
  {
    return '{{%courier_blueprints}}';
  }

  public function beforeSave($isNew): bool
  {
    if (!parent::beforeSave($isNew)) {
      return false;
    }

    // Enforce false before going to the database
    if(is_null($this->enabled) || $this->enabled === '') {
      $this->enabled = false;
    }

    return true;
  }

  public function getDeliveries(): ActiveQueryInterface
  {
    return $this->hasMany(
      Delivery::class,
      ['blueprintId' => 'id']
    );
  }
}
