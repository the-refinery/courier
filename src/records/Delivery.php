<?php

namespace refinery\courier\records;

use refinery\courier\Courier;

use Craft;
use craft\db\ActiveRecord;
use refinery\courier\records\Blueprint;
use yii\db\ActiveQueryInterface;

class Delivery extends ActiveRecord
{
  public static function tableName()
  {
    return '{{%courier_deliveries}}';
  }

  public function getBlueprint(): ActiveQueryInterface
  {
    return $this->hasOne(
      Blueprint::class,
      ['id' => 'blueprintId']
    );
  }
}
