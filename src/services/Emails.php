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
// use yii\base\Event;

/**
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 */

class Emails extends Component
{
	const EVENT_AFTER_BLUEPRINT_EMAIL_SENT = 'afterBlueprintEmailSent';
	const EVENT_AFTER_BLUEPRINT_EMAIL_FAILED = 'afterBlueprintEmailFailed';

}
