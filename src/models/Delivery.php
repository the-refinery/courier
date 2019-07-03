<?php

namespace refinery\courier\models;

use refinery\courier\Courier;

use Craft;
use craft\base\Model;

class Delivery extends Model
{
  public $id;
  public $uid;
  public $blueprintId;
  public $toEmail;
  public $errorMessages;
  public $success;
  public $dateCreated;
  public $dateUpdated;
  public $blueprint;

  public function rules()
  {
    return [
      // Required fields
      [
        [
          'blueprintId'
        ],
        'required'
      ],

      // Safe fields
      [
        [
          'id',
          'toEmail',
          'errorMessages',
          'success',
          'dateCreated',
          'dateUpdated',
          'uid',
          'blueprint'
        ],
        'safe'
      ]

    ];
  }
}
