<?php

namespace refinery\courier\models;

use refinery\courier\Courier;
use Craft;
use craft\base\Model;

class Blueprint extends Model
{
  public $id;
  public $name;
  public $fromName = "";
  public $htmlEmailTemplatePath;
  public $toEmail;
  public $fromEmail;
  public $emailSubject;
  public $toName;
  public $replyToEmail = "";
  public $ccEmail = "";
  public $bccEmail = "";
  public $textEmailTemplatePath = "";
  public $description = "";
  public $eventTriggerConditions = "";
  public $eventTriggers = "";
  public $enabled = true;

  public function eventTriggersJsonArray() {
    if(is_null($this->eventTriggers)) {
      return null;
    }

    if(empty($this->eventTriggers)){
      return null;
    }

    $triggers = [];

    foreach(json_decode($this->eventTriggers, true) as $eventTrigger) {
      array_push($triggers, json_decode($eventTrigger, true));
    }

    return $triggers;
  }

  public function rules()
  {
    return [
      // Required fields
      [
        [
          'name',
          'htmlEmailTemplatePath',
          'emailSubject',
          'fromEmail',
          'eventTriggers',
          'eventTriggerConditions'
        ],
        'required'
      ],

      // Safe fields
      [
        [
          'id',
          'name',
          'fromName',
          'htmlEmailTemplatePath',
          'toEmail',
          'fromEmail',
          'emailSubject',
          'toName',
          'replyToEmail',
          'ccEmail',
          'bccEmail',
          'textEmailTemplatePath',
          'description',
          'eventTriggerConditions',
          'eventTriggers',
          'enabled',
        ],
        'safe'
      ]

    ];
  }
}
