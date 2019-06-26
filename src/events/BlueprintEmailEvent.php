<?php

namespace refinery\courier\events;

/**
 * ModelEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class BlueprintEmailEvent extends \yii\base\ModelEvent
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether the model is brand new
     */
    public $blueprint;
    public $renderVariables;
    public $success;
    public $errorMessage;
}
