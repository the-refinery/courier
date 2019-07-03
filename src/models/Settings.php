<?php

namespace refinery\courier\models;

use refinery\courier\Courier;

use Craft;
use craft\base\Model;

class Settings extends Model
{
  public $deliveriesRecordLimit = 50;

  public function rules()
  {
    return [
      // Required
      [
        [
          'deliveriesRecordLimit',
        ],
        'required'
      ],
    ];
  }
}
