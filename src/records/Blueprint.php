<?php
/**
 * Courier plugin for Craft CMS 3.x
 *
 * This is a CraftCMS 3 fork of the original Courier plugin. The original project can be found here: https://github.com/therefinerynz/courier
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2019 The Refinery
 */

namespace refinery\courier\records;

use refinery\courier\Courier;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 */
class Blueprint extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%courier_blueprints}}';
    }

    // public function beforeSave(bool $isNew): bool
    public function beforeSave($isNew): bool
    {
        if (!parent::beforeSave($isNew)) {
            return false;
        }

        // Enforce false before going to the database
        if(is_null($this->enabled) || $this->enabled === '') {
            $this->enabled = false;
        }

        return true;
    }

}
