<?php

namespace refinery\courier\controllers;

// use Craft;
// use craft\base\Volume;
// use craft\base\VolumeInterface;
// use craft\elements\Asset;
// use craft\helpers\ArrayHelper;
// use craft\helpers\Json;
// use craft\helpers\UrlHelper;
// use craft\volumes\Local;
// use craft\volumes\MissingVolume;
// use craft\web\Controller;
// use yii\web\ForbiddenHttpException;
// use yii\web\NotFoundHttpException;
// use yii\web\Response;


// use barrelstrength\sproutbaseemail\models\SimpleRecipient;
// use barrelstrength\sproutbaseemail\models\SimpleRecipientList;
// use barrelstrength\sproutemail\services\SentEmails;
// use craft\mail\Mailer;
// use craft\mail\Message;
use refinery\courier\Courier;
use craft\web\Controller;
use Craft;
use yii\base\Exception;
use yii\web\Response;
use refinery\courier\records\Blueprint;
use refinery\courier\models\Blueprint as BlueprintModel;
use refinery\courier\records\Event as CourierEventRecord;

// use barrelstrength\sproutbaseemail\models\ModalResponse;
// use barrelstrength\sproutemail\elements\SentEmail;
// use barrelstrength\sproutemail\SproutEmail;
// use Egulias\EmailValidator\EmailValidator;
// use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
// use Egulias\EmailValidator\Validation\RFCValidation;


class BlueprintsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
      $this->requireAdmin();
    }

    public function actionIndex(): Response
    {
      // CONVERSION: $blueprints = craft()->courier_blueprints->getAllBlueprints();
      // CONVERSION: return $this->renderTemplate('courier/blueprints', compact('blueprints'));
      $variables = [];

      $blueprints = Courier::getInstance()
        ->blueprints
        ->getAllBlueprints();

      $eventLookup = Courier::getInstance()
        ->events
        ->eventIdMappingFromBlueprints($blueprints);





      /*
      $blueprintEventIds = [];

      // Gather up all the possible Courier Event IDs so that we can make one
      // database call to get them and use them in a lookup table below.
      foreach($blueprints as $blueprint) {
        if(!empty($blueprint->eventTriggers)) {
          foreach($blueprint->eventTriggersJsonArray() as $eventTriggerId) {
            array_push($blueprintEventIds, $eventTriggerId);
          }
        }
      }

      $blueprintEventIds = array_unique($blueprintEventIds);

      // Get all Courier events by the IDs above
      // TODO: This should probably be a service method of some kind.
      $events = Courier::getInstance()
        ->events
        ->getAllEvents(
          CourierEventRecord::find()
            ->where(['id' => $blueprintEventIds])
        );

      // Create a lookup table (dictionary)
      // [event id] = event model
      $eventLookup = [];

      foreach($events as $event) {
        $eventLookup[$event->id] = $event;
      }
      */




      $variables["blueprints"] = $blueprints;
      $variables["eventLookup"] = $eventLookup;

      return $this->renderTemplate('courier/blueprints', $variables);
    }

    public function actionCreate(): Response
    {
      /*
      $variables = craft()->urlManager->getRouteParams()['variables'];
      // Create a fresh Blueprint, or get the invalid one that was not saved yet
      if (empty($variables['blueprint'])) {
        $variables['blueprint'] = new Courier_BlueprintModel();
      }

      $variables['title'] = Craft::t('Create new blueprint');

      return $this->renderTemplate('courier/_blueprint', $variables);
      */

      // $variables['title'] = Craft::t('Create new blueprint');
      $variables['title'] = 'Create new blueprint';
      $variables['blueprint'] = new Blueprint();
      $variables['availableEvents'] = $this->buildAvailableEventsCheckboxOptions(
        // Courier::getInstance()->settings->availableEvents
        Courier::getInstance()
          ->events
          ->getAllEvents()
      );
      return $this->renderTemplate('courier/_blueprint', $variables);
    }

    private function buildAvailableEventsCheckboxOptions($availableEvents)
    {
      $options = [];
      foreach($availableEvents as $availableEvent)
      {
        // if($availableEvent['enabled'])
        // {
          $option = array(
            "label" => "Class: <b>{$availableEvent["eventClass"]}</b>, Handle: <b>{$availableEvent["eventHandle"]}</b>",
            "value" => $availableEvent->id
            // "value" => json_encode(
            //   array(
            //     "eventClass" => $availableEvent["eventClass"],
            //     "eventHandle" => $availableEvent["eventHandle"],
            //   )
            // ),
          );
          array_push($options, $option);
        // }
      }

      return $options;
    }

    public function actionSave(): Response
    {
      $this->requirePostRequest();

      $blueprint = new BlueprintModel();
      $request = Craft::$app->getRequest();

      $blueprint->id 						= $request->getParam('blueprintId', $blueprint->id);
      $blueprint->name 					= $request->getParam('name', $blueprint->name);
      $blueprint->description 	= $request->getParam('description', $blueprint->description);
      $blueprint->enabled 			= $request->getParam('enabled', $blueprint->enabled);
      $blueprint->emailSubject 	= $request->getParam('emailSubject', $blueprint->emailSubject);
      $blueprint->toEmail 			= $request->getParam('toEmail', $blueprint->toEmail);
      $blueprint->toName 				= $request->getParam('toName', $blueprint->toName);
      $blueprint->fromEmail 		= $request->getParam('fromEmail', $blueprint->fromEmail);
      $blueprint->fromName 			= $request->getParam('fromName', $blueprint->fromName);
      $blueprint->replyToEmail	= $request->getParam('replyToEmail', $blueprint->replyToEmail);
      $blueprint->ccEmail 			= $request->getParam('ccEmail', $blueprint->ccEmail);
      $blueprint->bccEmail 			= $request->getParam('bccEmail', $blueprint->bccEmail);
      $blueprint->eventTriggers = $request->getParam('eventTriggers', $blueprint->eventTriggers);
      $blueprint->htmlEmailTemplatePath 	= $request->getParam('htmlEmailTemplatePath', $blueprint->htmlEmailTemplatePath);
      $blueprint->textEmailTemplatePath 	= $request->getParam('textEmailTemplatePath', $blueprint->textEmailTemplatePath);
      $blueprint->eventTriggerConditions 	= $request->getParam('eventTriggerConditions', $blueprint->eventTriggerConditions);

      // Validate and save the blueprint
      /* CONVERSION
      if (!craft()->courier_blueprints->saveBlueprint($blueprint)) {
        craft()->userSession->setError(Craft::t('Couldn’t save blueprint.'));

        // Send the invalid blueprint back to the template
        return craft()->urlManager->setRouteVariables(['blueprint' => $blueprint]);
      }
      */

      if (!Courier::getInstance()->blueprints->saveBlueprint($blueprint)) {
        Craft::$app->getSession()->setError(Craft::t('courier', 'Couldn’t save blueprint.'));

        $availableEvents = $this->buildAvailableEventsCheckboxOptions(
          Courier::getInstance()
            ->events
            ->getAllEvents()
        );

        Craft::$app->getUrlManager()->setRouteParams([
          'blueprint' => $blueprint,
          'availableEvents' => $availableEvents
        ]);

        return $this->renderTemplate('courier/_blueprint', [
          'blueprint' => $blueprint,
          'availableEvents' => $availableEvents
        ]);
      }

      Craft::$app->getSession()->setNotice(Craft::t('courier', 'Blueprint saved.'));

      return $this->redirect("courier/blueprints");
    }

    /**
     * Edit a blueprint
     *
       * @return Response
     * @throws HttpException
     */
    public function actionEdit() : Response
    {
      $variables = Craft::$app->getUrlManager()->getRouteParams([
        'variables'
      ]);

      $variables['availableEvents'] = $this->buildAvailableEventsCheckboxOptions(
        Courier::getInstance()
          ->events
          ->getAllEvents()
      );

      // Get blueprint by id if it is not loaded already
      if (empty($variables['blueprint'])) {
        $variables['blueprint'] = Courier::getInstance()
          ->blueprints
          ->getBlueprintById($variables['id']);
      }

      // Could not find requested Blueprint
      if ($variables['blueprint'] === null) {
        throw new HttpException(404);
      }

      $variables['title'] = $variables['blueprint']->name;

      return $this->renderTemplate('courier/_blueprint', $variables);
    }

    /**
     * Shows the asset volume list.
     *
     * @return Response
     */
    // public function actionVolumeIndex(): Response
    // {
    //     $variables = [];
    //     $variables['volumes'] = Craft::$app->getVolumes()->getAllVolumes();

    //     return $this->renderTemplate('settings/assets/volumes/_index', $variables);
    // }

    // /**
    //  * Edit an asset volume.
    //  *
    //  * @param int|null $volumeId The volume’s ID, if editing an existing volume.
    //  * @param VolumeInterface|null $volume The volume being edited, if there were any validation errors.
    //  * @return Response
    //  * @throws ForbiddenHttpException if the user is not an admin
    //  * @throws NotFoundHttpException if the requested volume cannot be found
    //  */
    // public function actionEditVolume(int $volumeId = null, VolumeInterface $volume = null): Response
    // {
    //     $this->requireAdmin();

    //     $volumes = Craft::$app->getVolumes();

    //     $missingVolumePlaceholder = null;

    //     /** @var Volume $volume */
    //     if ($volume === null) {
    //         if ($volumeId !== null) {
    //             $volume = $volumes->getVolumeById($volumeId);

    //             if ($volume === null) {
    //                 throw new NotFoundHttpException('Volume not found');
    //             }

    //             if ($volume instanceof MissingVolume) {
    //                 $missingVolumePlaceholder = $volume->getPlaceholderHtml();
    //                 $volume = $volume->createFallback(Local::class);
    //             }
    //         } else {
    //             $volume = $volumes->createVolume(Local::class);
    //         }
    //     }

    //     /** @var string[]|VolumeInterface[] $allVolumeTypes */
    //     $allVolumeTypes = $volumes->getAllVolumeTypes();

    //     // Make sure the selected volume class is in there
    //     if (!in_array(get_class($volume), $allVolumeTypes, true)) {
    //         $allVolumeTypes[] = get_class($volume);
    //     }

    //     $volumeInstances = [];
    //     $volumeTypeOptions = [];

    //     foreach ($allVolumeTypes as $class) {
    //         if ($class === get_class($volume) || $class::isSelectable()) {
    //             $volumeInstances[$class] = $volumes->createVolume($class);

    //             $volumeTypeOptions[] = [
    //                 'value' => $class,
    //                 'label' => $class::displayName()
    //             ];
    //         }
    //     }

    //     // Sort them by name
    //     ArrayHelper::multisort($volumeTypeOptions, 'label');

    //     $isNewVolume = !$volume->id;

    //     if ($isNewVolume) {
    //         $title = Craft::t('app', 'Create a new asset volume');
    //     } else {
    //         $title = trim($volume->name) ?: Craft::t('app', 'Edit Volume');
    //     }

    //     $crumbs = [
    //         [
    //             'label' => Craft::t('app', 'Settings'),
    //             'url' => UrlHelper::url('settings')
    //         ],
    //         [
    //             'label' => Craft::t('app', 'Assets'),
    //             'url' => UrlHelper::url('settings/assets')
    //         ],
    //         [
    //             'label' => Craft::t('app', 'Volumes'),
    //             'url' => UrlHelper::url('settings/assets')
    //         ],
    //     ];

    //     $tabs = [
    //         'settings' => [
    //             'label' => Craft::t('app', 'Settings'),
    //             'url' => '#assetvolume-settings'
    //         ],
    //         'fieldlayout' => [
    //             'label' => Craft::t('app', 'Field Layout'),
    //             'url' => '#assetvolume-fieldlayout'
    //         ],
    //     ];

    //     return $this->renderTemplate('settings/assets/volumes/_edit', [
    //         'volumeId' => $volumeId,
    //         'volume' => $volume,
    //         'isNewVolume' => $isNewVolume,
    //         'volumeTypes' => $allVolumeTypes,
    //         'volumeTypeOptions' => $volumeTypeOptions,
    //         'missingVolumePlaceholder' => $missingVolumePlaceholder,
    //         'volumeInstances' => $volumeInstances,
    //         'title' => $title,
    //         'crumbs' => $crumbs,
    //         'tabs' => $tabs
    //     ]);
    // }

    // /**
    //  * Saves an asset volume.
    //  *
    //  * @return Response|null
    //  */
    // public function actionSaveVolume()
    // {
    //     $this->requirePostRequest();

    //     $request = Craft::$app->getRequest();
    //     $volumes = Craft::$app->getVolumes();

    //     $type = $request->getBodyParam('type');

    //     $volumeId = $request->getBodyParam('volumeId');

    //     $volumeData = [
    //         'id' => $volumeId,
    //         'type' => $type,
    //         'name' => $request->getBodyParam('name'),
    //         'handle' => $request->getBodyParam('handle'),
    //         'hasUrls' => (bool)$request->getBodyParam('hasUrls'),
    //         'url' => $request->getBodyParam('url'),
    //         'settings' => $request->getBodyParam('types.' . $type)
    //     ];

    //     // If this is an existing volume, populate with properties unchangeable by this action.
    //     if ($volumeId) {
    //         /** @var Volume $savedVolume */
    //         $savedVolume = $volumes->getVolumeById($volumeId);
    //         $volumeData['uid'] = $savedVolume->uid;
    //         $volumeData['sortOrder'] = $savedVolume->sortOrder;
    //     }

    //     /** @var Volume $volume */
    //     $volume = $volumes->createVolume($volumeData);

    //     // Set the field layout
    //     $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
    //     $fieldLayout->type = Asset::class;
    //     $volume->setFieldLayout($fieldLayout);

    //     $session = Craft::$app->getSession();

    //     if (!$volumes->saveVolume($volume)) {
    //         $session->setError(Craft::t('app', 'Couldn’t save volume.'));

    //         // Send the volume back to the template
    //         Craft::$app->getUrlManager()->setRouteParams([
    //             'volume' => $volume
    //         ]);

    //         return null;
    //     }

    //     $session->setNotice(Craft::t('app', 'Volume saved.'));

    //     return $this->redirectToPostedUrl();
    // }

    // /**
    //  * Reorders asset volumes.
    //  *
    //  * @return Response
    //  */
    // public function actionReorderVolumes(): Response
    // {
    //     $this->requirePostRequest();
    //     $this->requireAcceptsJson();

    //     $volumeIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
    //     Craft::$app->getVolumes()->reorderVolumes($volumeIds);

    //     return $this->asJson(['success' => true]);
    // }

    // /**
    //  * Deletes an asset volume.
    //  *
    //  * @return Response
    //  */
    // public function actionDeleteVolume(): Response
    // {
    //     $this->requirePostRequest();
    //     $this->requireAcceptsJson();

    //     $volumeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

    //     Craft::$app->getVolumes()->deleteVolumeById($volumeId);

    //     return $this->asJson(['success' => true]);
    // }
}