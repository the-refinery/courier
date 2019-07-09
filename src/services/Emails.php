<?php

namespace refinery\courier\services;

use refinery\courier\Courier;
use Craft;
use craft\base\Component;
use refinery\courier\models\Blueprint as BlueprintModel;
use refinery\courier\events\BlueprintEmailEvent;
use craft\mail\Message;
use yii\base\Event;

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
      $success = Craft::$app
        ->mailer
        ->send($email);

      $resultEventParams['success'] = $success;
    } catch (\Throwable $e) {
      Courier::log(
        "\nCould not send email for the blueprint named {$blueprint->name}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      throw new \Exception($e);

      $resultEventParams['error'] = $error;

      // Fire a new onBlueprintEmailFailedEvent
      $event = new BlueprintEmailEvent($this, $resultEventParams);
      $this->onAfterBlueprintEmailFailed($event);

      return false;
    }

    if ($success) {
      // Fire a new onBlueprintEmailSentEvent
      $event = new BlueprintEmailEvent([
        'blueprint' => $blueprint,
        'renderVariables' => $renderVariables,
        'success' => true
      ]);

      $this->onAfterBlueprintEmailSent($event);
    } else {
      Courier::log(
        "\nUnknown error occurred when attempting to send email for {$blueprint->name}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      $error = "Unknown error occurred when attempting to send email for '{$blueprint->name}'.\r\nCheck your Craft log for more details.";

      // Fire a new onBlueprintEmailFailedEvent
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

  public function onAfterBlueprintEmailSent(Event $event)
  {
    $this->trigger(self::EVENT_AFTER_BLUEPRINT_EMAIL_SENT, $event);
  }

  public function onAfterBlueprintEmailFailed(Event $event) {
    $this->trigger(self::EVENT_AFTER_BLUEPRINT_EMAIL_FAILED, $event);
  }

  private function _createBlueprintEmail(BlueprintModel $blueprint, array $renderVariables)
  {
    $resultEventParams = compact('blueprint');

    // Try to render the blueprint templates and settings
    try {
      $blueprint = $this
        ->_renderBlueprintSettings(
            $blueprint,
            $renderVariables
          );

      // Blueprint model should only be available in the email body templates
      $emailVariables = array_merge($renderVariables, compact('blueprint'));

      $emailTemplates = $this
        ->_renderEmailTemplates(
          $blueprint,
          $emailVariables
        );
    } catch (Exception $e) {
      Courier::log(
        "\nThere was a problem rendering blueprint settings for blueprint {$blueprint->name}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
        Logger::LEVEL_ERROR
      );

      return null;
    }

    $email = new Message();
    $email->setTo([$blueprint->toEmail => $blueprint->toName]);
    $email->setFrom([$blueprint->fromEmail => $blueprint->fromName]);
    $email->setSubject($blueprint->emailSubject);
    $email->setHtmlBody($emailTemplates['HTML Email Template']);

    // Set optional fields on the email
    if (!empty($blueprint->replyToEmail)) {
      $email->setReplyTo($blueprint->replyToEmail);
    }

    if (!empty($blueprint->ccEmail)) {
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

  private function _renderEmailTemplates(BlueprintModel $blueprint, array $renderVariables)
  {
    $oldTemplateMode = \Craft::$app
      ->view
      ->getTemplateMode();

    // Switch template modes to allow us to locate the template paths
    \Craft::$app
      ->view
      ->setTemplateMode(\craft\web\View::TEMPLATE_MODE_SITE);

    $emailTemplates = [
      'HTML Email Template' => $blueprint->htmlEmailTemplatePath
    ];

    if ($blueprint->textEmailTemplatePath) {
      $emailTemplates = array_merge(
        $emailTemplates,
        ['Text Email Template' => $blueprint->textEmailTemplatePath]
      );
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
          $renderableString = \Craft::$app
            ->view
            ->renderString(
              $renderableString,
              $renderVariables
            );
        }
        catch (\Exception $e) {
          // Template path parse error
          Courier::log(
            "\nTemplate parse error encountered while parsing field {$attributeHandle} for blueprint {$blueprint->name}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
            Logger::LEVEL_ERROR
          );

          throw new Exception($error);
        }
      } while ($this->_hasTwigBrackets($renderableString) && $lastRenderedResult !== $renderableString);

      $templatePath = $this->_stripTwigBrackets($renderableString);

      // Try to resolve the rendered template path
      $templateExists = \Craft::$app
        ->view
        ->doesTemplateExist($templatePath);

      if (!$templateExists) {
        // Log here
        $error = "Email template does not exist at path {$templatePath} for blueprint {$blueprint->name}";
        Courier::log(
          "\nEmail template does not exist at path {$templatePath} for blueprint {$blueprint->name}\n",
          Logger::LEVEL_ERROR
        );

        throw new \Exception($error);
      }

      $renderableTemplate = null;

      do {
        try {
          $lastRenderedResult = $renderableTemplate;

          // Render template that dynamic path points to
          $renderableTemplate = \Craft::$app
            ->view
            ->renderTemplate(
              $templatePath,
              $renderVariables
            );
        }
        catch (\Exception $e) {
          // Template file parse error
          $errorMessage = $e->getMessage();
          $error = "Template parse error encountered while parsing the {$attributeHandle} file located at path {$templatePath} for the blueprint named {$blueprint->name}:\r\n{$errorMessage}";

          Courier::log(
            $error,
            Logger::LEVEL_ERROR
          );

          throw new \Exception($error);
        }
      } while ($this->_hasTwigBrackets($renderableTemplate) && $lastRenderedResult !== $renderableTemplate);

      $renderedTemplate = $this->_stripTwigBrackets($renderableTemplate);

      $emailTemplates[$attributeHandle] = $this->_stripEntities($renderedTemplate);
    }

    // Return to the original template mode
    \Craft::$app
      ->view
      ->setTemplateMode($oldTemplateMode);

    return $emailTemplates;
  }

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
          // Render the string with Twig
          $lastRenderedResult = $renderableString;
          $renderableString = \Craft::$app
            ->view
            ->renderString(
              $renderableString,
              $renderVariables
            );

          $renderableString = $this->_stripEntities($renderableString);
        }
        catch (\Exception $e) {
          // Template parse error
          Courier::log(
            "\nTemplate parse error encountered while parsing field {$attributeHandle} for blueprint {$blueprint->name}:\n{$e->getMessage()}\n{$e->getTraceAsString()}",
            Logger::LEVEL_ERROR
          );

          throw new Exception($error);
        }
      } while ($this->_hasTwigBrackets($renderableString) && $lastRenderedResult !== $renderableString);

      $renderedString = $this->_stripTwigBrackets($renderableString);

      // Commas and semicolons can separate multiple item fields. Explode these items into an array.
      if (in_array($attributeHandle, $multipleItemFields)) {
        $emails = str_replace(';', ',', $renderedString);
        $emails = explode(',', $emails);
        $emailsArr = [];

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

  private function _camelCaseToTitle($camelStr)
  {
    $intermediate = preg_replace('/(?!^)([[:upper:]][[:lower:]]+)/', ' $0', $camelStr);
    $titleStr = preg_replace('/(?!^)([[:lower:]])([[:upper:]])/', '$1 $2', $intermediate);

    return ucwords($titleStr);
  }

  private function _stripEntities($string)
  {
    return trim(preg_replace_callback("/(&#[0-9]+;)/", function($m) {
      return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
    }, $string));
  }

  private function _hasTwigBrackets($template)
  {
    return preg_match('/\{{2}.*?\}{2}|\{\%.*?\%\}/', $template);
  }

  private function _stripTwigBrackets($template)
  {
    return preg_replace('/\{{2}.*?\}{2}|\{\%.*?\%\}/', '', $template);
  }
}
