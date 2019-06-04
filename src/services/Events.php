<?php
/**
 * Courier plugin for Craft CMS 3.x
 *
 * This is a CraftCMS 3 fork of the original Courier plugin. The original project can be found here: https://github.com/therefinerynz/courier
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2019 The Refinery
 */

namespace refinery\courier\services;

use refinery\courier\Courier;

use Craft;
use craft\base\Component;
use yii\base\Event;

/**
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 */
// class EventsService extends Component
class Events extends Component
{
    // Public Methods
    // =========================================================================

	/**
	 * Get array of available events as determined in Courier's settings
	 *
	 * @return array
	 */
	public function getAvailableEvents()
	{
		// CONVERSION: $courierSettings = craft()->plugins->getPlugin('courier')->getSettings();
		$courierSettings = Courier::$plugin->getSettings();

		$availableEvents = [];
		foreach ($courierSettings->availableEvents as $eventOption) {
			if ($eventOption['enabled']) {
				$availableEvents[$eventOption['eventClass']] = $eventOption['eventClass'];
				$availableEvents[$eventOption['eventHandle']] = $eventOption['eventHandle'];
			}
		}

		return $availableEvents;
	}

	/**
	 * Setup event listeners of all blueprints
	 *
	 * @return void
	 */
	public function setupEventListeners()
	{
		// CONVERSION: $blueprints = craft()->courier_blueprints->getAllBlueprints();
		// $blueprints = craft()->courier_blueprints->getAllBlueprints();
		$blueprints = Courier::getInstance()->blueprints->getAllBlueprints();
		// print_r($blueprints, true);

        // Craft::info(
        //     Craft::t(
        //         'courier',
        //         '{name} plugin loaded',
        //         ['name' => $this->name]
        //     ),
        //     __METHOD__
				// );

		// Courier::error("JFKDJSLFJKDS", true);
		// Craft::warning(print_r($blueprints, true), "courier");

		$availableEvents = $this->getAvailableEvents();

		// Setup event listeners for each blueprint
		foreach ($blueprints as $blueprint) {
			if (!$blueprint->eventTriggers) {
				continue;
			}
			foreach ($blueprint->eventTriggers as $event) {
				// Is event currently enabled?
				if (!isset($availableEvents[$event])) {
					continue;
				}
				craft()->on($event, function(Event $event) use ($blueprint) {
					craft()->courier_blueprints->checkEventConditions($event, $blueprint);
				});
			}
		}

		// On the event that an email is sent, create a successful delivery record
		// CONVERSION: craft()->on('courier_emails.onAfterBlueprintEmailSent', [
		// 	craft()->courier_deliveries,
		// 	'createDelivery'
		// ]);

		Event::on(
			Emails::class,
			Emails::EVENT_AFTER_BLUEPRINT_EMAIL_SENT,
			function(Event $event){
				Courier::getInstance()
					->deliveries
					->createDelivery($event);
			}
		);


		// On the event that an email fails to send, create a failed delivery record
		// CONVERSION: craft()->on('courier_emails.onAfterBlueprintEmailFailed', [
		// 	craft()->courier_deliveries,
		// 	'createDelivery',
		// ]);
		Event::on(
			Emails::class,
			Emails::EVENT_AFTER_BLUEPRINT_EMAIL_FAILED,
			function(Event $event){
				Courier::getInstance()
					->deliveries
					->createDelivery($event);
			}
		);
	}
}
