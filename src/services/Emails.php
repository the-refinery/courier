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
use refinery\courier\models\Blueprint as BlueprintModel;
use refinery\courier\events\BlueprintEmailEvent;
use craft\mail\Message;
use yii\base\Event;

/**
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 */

class Emails extends Component
{
	const EVENT_AFTER_BLUEPRINT_EMAIL_SENT = 'afterBlueprintEmailSent';
	const EVENT_AFTER_BLUEPRINT_EMAIL_FAILED = 'afterBlueprintEmailFailed';

	public function sendBlueprintEmail(BlueprintModel $blueprint, array $renderVariables)
	{
		$resultEventParams = [
			'blueprint' => $blueprint,
			'success' => false,
		];

		$email = $this->_createBlueprintEmail($blueprint, $renderVariables);
		$resultEventParams['email'] = $email;

		// Something went wrong creating the email...
		if (!$email) {
			return false;
		}

		// Now try to send the email
		try {
			// $success = craft()->email->sendEmail($email);

			$success = Craft::$app
				->mailer
				->send($email);
				// ->send($message);

			$resultEventParams['success'] = $success;
		} catch (\Exception $e) {
			// Log error
			// $error = Craft::t("Could not send email for the blueprint named “{blueprint}”.\r\n{error}", [
			// 	'blueprint' => $blueprint->name,
			// 	'error' => $e->getMessage(),
			// ]);
			// CourierPlugin::log($error, LogLevel::Error, true);

			throw new \Exception($e);

			$resultEventParams['error'] = $error;

			// Fire a new onBlueprintEmailFailedEvent
			$event = new BlueprintEmailEvent($this, $resultEventParams);
			$this->onAfterBlueprintEmailFailed($event);

			return false;
		}

		if ($success) {
			// Log here
			// $message = Craft::t('Successfully sent email for “{blueprint}”.', [
			// 	'blueprint' => $blueprint->name,
			// ]);
			// CourierPlugin::log($message, LogLevel::Info);

			// Fire a new onBlueprintEmailSentEvent

			/*
			$event = new Event($this, $resultEventParams);
			$this->onAfterBlueprintEmailSent($event);
			*/

			// $event = new Event();
			$event = new BlueprintEmailEvent([
				'blueprint' => $blueprint,
				'renderVariables' => $renderVariables,
				'success' => true
			]);

			$this->onAfterBlueprintEmailSent($event);


			// 83         $event = new RegisterFeedMeDataTypesEvent([
			// 	84             'dataTypes' => [
			// 	85                 Atom::class,
			// 	86                 Csv::class,
			// 	87                 GoogleSheet::class,
			// 	88                 Json::class,
			// 	89                 Rss::class,
			// 	90                 Xml::class,
			// 	91             ],
			// 	92         ]);
			// 	93
			// 	94         $this->trigger(self::EVENT_REGISTER_FEED_ME_DATA_TYPES, $event);



		} else {
			// Log here
			// $error = Craft::t("Unknown error occurred when attempting to send email for “{blueprint}”.\r\nCheck your Craft log for more details.", [
			// 	'blueprint' => $blueprint->name,
			// ]);
			// CourierPlugin::log($error, LogLevel::Error);
			// $resultEventParams['error'] = $error;

			// // Fire a new onBlueprintEmailFailedEvent
			// $event = new Event($this, $resultEventParams);
			// $this->onAfterBlueprintEmailFailed($event);
			$error = "Unknown error occurred when attempting to send email for '{$blueprint->name}'.\r\nCheck your Craft log for more details.";
			$event = new BlueprintEmailEvent([
				'blueprint' => $blueprint,
				'renderVariables' => $renderVariables,
				'success' => false,
				'errorMessage' => $error
			]);

			$this->onAfterBlueprintEmailFailed($event);
		}

		return true;
	}

	/**
	 * Fire the event "onAfterBlueprintEmailSent"
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function onAfterBlueprintEmailSent(Event $event)
	{
			// 152         $this->trigger(self::EVENT_BEFORE_PUSH, $event);
		// $this->raiseEvent('onAfterBlueprintEmailSent', $event);

		// const EVENT_AFTER_BLUEPRINT_EMAIL_SENT = 'afterBlueprintEmailSent';
		// const EVENT_AFTER_BLUEPRINT_EMAIL_FAILED = 'afterBlueprintEmailFailed';
		$this->trigger(self::EVENT_AFTER_BLUEPRINT_EMAIL_SENT, $event);
	}

	public function onAfterBlueprintEmailFailed(Event $event)
	{
		$this->trigger(self::EVENT_AFTER_BLUEPRINT_EMAIL_FAILED, $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param Blueprint $blueprint
	 * @param array $renderVariables
	 *
	 * @return Email|null
	 */
	private function _createBlueprintEmail(BlueprintModel $blueprint, array $renderVariables)
	{
		$resultEventParams = compact('blueprint');

		// Try to render the blueprint templates and settings
		try {
			$blueprint = $this->_renderBlueprintSettings($blueprint, $renderVariables);

			// Blueprint model should only be available in the email body templates
			$emailVariables = array_merge($renderVariables, compact('blueprint'));

			$emailTemplates = $this->_renderEmailTemplates($blueprint, $emailVariables);
		} catch (Exception $e) {
			$error = Craft::t("Could not create email for the blueprint named “{blueprint}”.\r\n{error}", [
				'blueprint' => $blueprint->name,
				'error' => $e->getMessage(),
			]);
			CourierPlugin::log($error, LogLevel::Error, true);

			$resultEventParams['error'] = $error;

			// Fire a new onBlueprintEmailFailedEvent
			$event = new Event($this, $resultEventParams);
			$this->onAfterBlueprintEmailFailed($event);

			return null;
		}

		// $email = new EmailModel();
		$email = new Message();

		// Set required fields on the email
		/*
		$email->toFirstName = $blueprint->toName;
		$email->toEmail = $blueprint->toEmail;
		$email->fromEmail = $blueprint->fromEmail;
		$email->fromName = $blueprint->fromName;
		$email->subject = $blueprint->emailSubject;
		$email->htmlBody = $emailTemplates['HTML Email Template'];
		*/

		$email->setTo([$blueprint->toEmail => $blueprint->toName]);
		$email->setFrom([$blueprint->fromEmail => $blueprint->fromName]);
		$email->setSubject($blueprint->emailSubject);
		$email->setHtmlBody($emailTemplates['HTML Email Template']);

		// Set optional fields on the email
		if (!empty($blueprint->replyToEmail)) {
			// $email->replyTo =$blueprint->replyToEmail;
			$email->setReplyTo($blueprint->replyToEmail);
		}

		if (!empty($blueprint->ccEmail)) {
			// var_dump($blueprint->ccEmail);
			// die();
			$email->setCc($blueprint->ccEmail);
		}

		if (!empty($blueprint->bccEmail)) {
			$email->setBcc($blueprint->bccEmail);
		}

		// Set optional text email template if it exists
		if (isset($emailTemplates['Text Email Template'])) {
			$email->setTextBody($emailTemplates['Text Email Template']);
		}

		return $email;
	}

	/**
	 * @param BlueprintModel $blueprint
	 * @param array $renderVariables
	 *
	 * @return array|null $result
	 */
	private function _renderEmailTemplates(BlueprintModel $blueprint, array $renderVariables)
	{
		// $oldTemplateMode = craft()->templates->getTemplateMode();
		$oldTemplateMode = \Craft::$app
			->view
			->getTemplateMode();

		// Switch template modes to allow us to locate the template paths
		// craft()->templates->setTemplateMode(TemplateMode::Site);
		\Craft::$app
			->view
			->setTemplateMode(\craft\web\View::TEMPLATE_MODE_SITE);

		$emailTemplates = ['HTML Email Template' => $blueprint->htmlEmailTemplatePath];

		if ($blueprint->textEmailTemplatePath) {
			$emailTemplates = array_merge($emailTemplates, ['Text Email Template' => $blueprint->textEmailTemplatePath]);
		}

		foreach ($emailTemplates as $attributeHandle => $attributeValue) {
			$renderableString = trim($attributeValue);
			// Skip empty value
			if (!$renderableString) {
				continue;
			}
			do {
				// Try to render dynamic path
				try {
					$lastRenderedResult = $renderableString;
					// $renderableString = craft()->templates->renderString($renderableString, $renderVariables);
					$renderableString = \Craft::$app
						->view
						->renderString(
							$renderableString,
							$renderVariables
						);
				}
				// Template path parse error
				catch (\Exception $e) {
					// LOg here.
					// $errorMessage = $e->getMessage();
					// $error = Craft::t("Template parse error encountered while parsing field “{attributeHandle}” for the blueprint named “{blueprint}.”:\r\n{error}", [
					// 	'blueprint' => $blueprint->name,
					// 	'attributeHandle' => $attributeHandle,
					// 	'error' => $errorMessage,
					// ]);
					// CourierPlugin::log($error, LogLevel::Error, true);

					throw new Exception($error);
				}
			} while ($this->_hasTwigBrackets($renderableString) && $lastRenderedResult !== $renderableString);

			$templatePath = $this->_stripTwigBrackets($renderableString);

			// Try to resolve the rendered template path

			$templateExists = \Craft::$app
				->view
				->doesTemplateExist($templatePath);

			// if (!craft()->templates->doesTemplateExist($templatePath)) {
			if (!$templateExists) {
				// Log here
				$error = "Email template does not exist at path {$templatePath} for blueprint {$blueprint->name}";
				// $error = Craft::t('Email template does not exist at path “{templatePath}” for blueprint “{blueprint}”.', [
				// 	'templatePath' => $templatePath,
				// 	'blueprint' => $blueprint->name,
				// ]);
				// CourierPlugin::log($error, LogLevel::Error, true);

				throw new \Exception($error);
			}

			$renderableTemplate = null;

			do {
				try {
					$lastRenderedResult = $renderableTemplate;
					// Render template that dynamic path points to

					// $renderableTemplate = craft()->templates->render($templatePath, $renderVariables);
					$renderableTemplate = \Craft::$app
						->view
						->renderTemplate(
							$templatePath,
							$renderVariables
						);
					// $renderableTemplate = \Craft::$app
					// 	->view
					// 	->render(
					// 		$templatePath,
					// 		$renderVariables,
					// 		// why
					// 		// \Craft::$app->view->context
					// 	);
				}
				// Template file parse error
				catch (\Exception $e) {
					// Log here
					$errorMessage = $e->getMessage();
					$error = "Template parse error encountered while parsing the {$attributeHandle} file located at path {$templatePath} for the blueprint named {$blueprint->name}:\r\n{$errorMessage}";
					// $error = Craft::t("Template parse error encountered while parsing the {templateName} file located at path “{templatePath}” for the blueprint named “{blueprint}”:\r\n{error}", [
					// 	'blueprint' => $blueprint->name,
					// 	'templateName' => $attributeHandle,
					// 	'templatePath' => $templatePath,
					// 	'error' => $errorMessage,
					// ]);
					// CourierPlugin::log($error, LogLevel::Error, true);

					throw new \Exception($error);
				}
			} while ($this->_hasTwigBrackets($renderableTemplate) && $lastRenderedResult !== $renderableTemplate);

			$renderedTemplate = $this->_stripTwigBrackets($renderableTemplate);

			$emailTemplates[$attributeHandle] = $this->_stripEntities($renderedTemplate);
		}

		// Return to the original template mode
		// craft()->templates->setTemplateMode($oldTemplateMode);
		\Craft::$app
			->view
			->setTemplateMode($oldTemplateMode);

		return $emailTemplates;
	}

	/**
	 * Render a given Blueprint's fields with Twig
	 *
	 * @param BlueprintModel $blueprint
	 * @param array $renderVariables
	 *
	 * @return BlueprintModel - Blueprint with its settings rendered
	 */
	private function _renderBlueprintSettings(BlueprintModel $blueprint, array $renderVariables)
	{
		$renderableSettings = [
			'emailSubject' => $blueprint->emailSubject,
			'toName' => $blueprint->toName,
			'toEmail' => $blueprint->toEmail,
			'fromName' => $blueprint->fromName,
			'fromEmail' => $blueprint->fromEmail,
			'replyToEmail' => $blueprint->replyToEmail,
			'ccEmail' => $blueprint->ccEmail,
			'bccEmail' => $blueprint->bccEmail,
		];

		$multipleItemFields = [
			'ccEmail',
			'bccEmail'
		];

		// Render all settings on the Blueprint with Twig
		foreach ($renderableSettings as $attributeHandle => $attributeValue) {
			$renderableString = trim($attributeValue);
			// Skip empty value
			if (!$renderableString) {
				continue;
			}
			do {
				try {
					$lastRenderedResult = $renderableString;
					// Render the string with Twig
					// $renderableString = craft()->templates->renderString($renderableString, $renderVariables);

					$renderableString = \Craft::$app
						->view
						->renderString(
							$renderableString,
							$renderVariables
						);

					$renderableString = $this->_stripEntities($renderableString);
				}
				// Template parse error
				catch (\Exception $e) {
					// Log exception here.
					// $errorMessage = $e->getMessage();
					// $error = Craft::t("Template parse error encountered while parsing field “{attributeName}” for the blueprint named “{blueprint}”:\r\n{error}", [
					// 	'blueprint' => $blueprint->name,
					// 	'attributeName' => $this->_camelCaseToTitle($attributeHandle),
					// 	'error' => $errorMessage
					// ]);
					// CourierPlugin::log($error, LogLevel::Error, true);

					throw new Exception($error);
				}
			} while ($this->_hasTwigBrackets($renderableString) && $lastRenderedResult !== $renderableString);

			$renderedString = $this->_stripTwigBrackets($renderableString);

			// Commas and semicolons can separate multiple item fields. Explode these items into an array.
			if (in_array($attributeHandle, $multipleItemFields)) {
				$emails = str_replace(';', ',', $renderedString);
				$emails = explode(',', $emails);
				$emailsArr = [];
				// foreach ($emails as $email) {
				// 	$emailsArr[] = ['email' => trim($email)];
				// }
				foreach($emails as $email) {
					array_push($emailsArr, $email);
				}
				$blueprint[$attributeHandle] = $emailsArr;
			} else {
				$blueprint[$attributeHandle] = $renderedString;
			}
		}

		return $blueprint;
	}

	/**
	 * Convert camelCase string to Title Case
	 * From https://gist.github.com/justjkk/1402061
	 *
	 * @param str
	 *
	 * @return str $result
	 */
	private function _camelCaseToTitle($camelStr)
	{
		$intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', ' $0', $camelStr);
		$titleStr = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1 $2', $intermediate);

		return ucwords($titleStr);
	}

	/**
	 * Strip HTML entities from string and trim
	 * From http://php.net/manual/en/function.html-entity-decode.php#104617
	 *
	 * @param str $string
	 *
	 * @return str $result
	 */
	private function _stripEntities($string)
	{
		 return trim(preg_replace_callback("/(&#[0-9]+;)/", function($m) {
			 return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
		}, $string));
	}

	/**
	 * Check for Twig tags within a template string
	 *
	 * @param str $template
	 *
	 * @return bool
	 */
	private function _hasTwigBrackets($template)
	{
		return preg_match('/\{{2}.*?\}{2}|\{\%.*?\%\}/', $template);
	}

	/**
	 * Strip Twig tags from string (used in case of inability to render)
	 *
	 * @param str $template
	 *
	 * @return str
	 */
	private function _stripTwigBrackets($template)
	{
		return preg_replace('/\{{2}.*?\}{2}|\{\%.*?\%\}/', '', $template);
	}

}
