<?php

namespace refinery\courier\controllers;

use refinery\courier\Courier;
use craft\web\Controller;
use Craft;
use yii\base\Exception;
use yii\web\Response;
use refinery\courier\records\Delivery;

class DeliveriesController extends Controller
{
  public function init()
  {
    $this->requireAdmin();
  }

  public function actionIndex(): Response
  {
    $variables = [];
    $criteria = Delivery::find()
      ->orderBy("dateCreated DESC");

    $deliveries = Courier::getInstance()
      ->deliveries
      ->getAllDeliveries($criteria);

    $variables["deliveries"] = $deliveries;

    return $this->renderTemplate('courier/deliveries', $variables);
  }

  public function actionDelete(): Response
  {
    $this->requirePostRequest();
    $request = Craft::$app->getRequest();

    $id = $request->getParam("id");

    $result = Courier::getInstance()
      ->deliveries
      ->deleteDeliveryById($id);

    return $this->asJson([
      'success' => $result
    ]);
  }

  public function actionDeleteAll(): Response
  {
    $message = "";

    $result = Courier::getInstance()
      ->deliveries
      ->deleteAllDeliveries();

    if($result) {
      $message = Craft::t(
        'courier',
        'All deliveries deleted successfully.'
      );
    } else {
      $message = Craft::t(
        'courier',
        'There was a problem removing all deliveries. Please see log file for details.'
      );
    }

    Craft::$app
      ->getSession()
      ->setNotice(
        $message
      );

    return $this->renderTemplate('courier/deliveries');
  }
}