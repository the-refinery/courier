<?php

namespace refinery\courier\models;

use refinery\courier\Courier;

use Craft;
use craft\base\Model;

class Event extends Model
{
  public $id;
  public $uid;
  public $eventClass;
  public $eventHandle;
  public $description;
  public $enabled;
  public $dateCreated;
  public $dateUpdated;

  public function rules()
  {
    return [
      // Required fields
      [
        [
          'eventClass',
          'eventHandle'
        ],
        'required'
      ],

      // Safe fields
      [
        [
          'id',
          'uid',
          'eventClass',
          'eventHandle',
          'description',
          'enabled',
          'createCreated',
          'dateUpdated'
        ],
        'safe'
      ]
    ];
  }
}
