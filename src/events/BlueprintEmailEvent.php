<?php

namespace refinery\courier\events;

class BlueprintEmailEvent extends \yii\base\ModelEvent
{
  public $blueprint;
  public $renderVariables;
  public $success;
  public $errorMessage;
}
