<?php
/**
 * Courier plugin for Craft CMS 3.x
 *
 * This is a CraftCMS 3 fork of the original Courier plugin. The original project can be found here: https://github.com/therefinerynz/courier
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2019 The Refinery
 */

namespace refinery\courier\models;

use refinery\courier\Courier;

use Craft;
use craft\base\Model;

/**
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 */
class Blueprint extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    // public $someAttribute = 'Some Default';
    // public $fullName;

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

    // Public Methods
    // =========================================================================

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
        $rules = parent::rules();
        $rules[] = [['name', 'htmlEmailTemplatePath', 'emailSubject', 'fromEmail', 'eventTriggers', 'eventTriggerConditions'], 'required'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    // public function rules()
    // {
    //     return [
    //         ['someAttribute', 'string'],
    //         ['someAttribute', 'default', 'value' => 'Some Default'],
    //     ];
    // }
}
