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
class Settings extends Model
{

    public $deliveriesRecordLimit = 50;
    public $availableEvents = [
        [
            'event' => 'entries.onSaveEntry',
            'enabled' => true
        ]
    ];

    public function rules()
    {
        return [
            [['deliveriesRecordLimit', 'availableEvents'], 'required'],
        ];
    }

    // // Public Properties
    // // =========================================================================

    // /**
    //  * @var string
    //  */
    // public $someAttribute = 'Some Default';

    // // Public Methods
    // // =========================================================================

    // /**
    //  * @inheritdoc
    //  */
    // public function rules()
    // {
    //     return [
    //         ['someAttribute', 'string'],
    //         ['someAttribute', 'default', 'value' => 'Some Default'],
    //     ];
    // }
}
