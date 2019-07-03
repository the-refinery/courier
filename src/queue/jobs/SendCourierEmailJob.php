<?php

namespace refinery\courier\queue\jobs;

use Craft;
use refinery\courier\Courier;
use craft\queue\BaseJob;
use yii\base\Exception;

class SendCourierEmailJob extends BaseJob
{
  public $renderVariables;
  public $blueprintId;
  public $blueprintName;

  public function execute($queue)
  {
    $this->setProgress($queue, 0.0);

    $blueprint = Courier::getInstance()
      ->blueprints
      ->getBlueprintById(
        $this->blueprintId
      );

    $this->setProgress($queue, 0.5);

    Courier::getInstance()
      ->emails
      ->sendBlueprintEmail(
        $blueprint,
        $this->renderVariables
      );

    $this->setProgress($queue, 1.0);
  }

  protected function defaultDescription(): string
  {
    return "Sending Courier email for blueprint '{$this->blueprintName}'";
  }
}
