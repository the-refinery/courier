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
class Delivery extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    // public $someAttribute = 'Some Default';
    // public $fullName;
    public $id;
    public $uid;
    public $blueprintId;
    public $toEmail;
    public $errorMessages;
    public $success;
    public $dateCreated;
    public $dateUpdated;
    public $blueprint;

    public function rules()
    {
        return [
            [['blueprintId'], 'required'],
            [['id', 'toEmail', 'errorMessages', 'success', 'dateCreated', 'dateUpdated', 'uid', 'blueprint'], 'safe']
        ];
    }
}
