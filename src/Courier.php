<?php
/**
 * Courier plugin for Craft CMS 3.x
 *
 * This is a CraftCMS 3 fork of the original Courier plugin. The original project can be found here: https://github.com/therefinerynz/courier
 *
 * @link      https://the-refinery.io
 * @copyright Copyright (c) 2019 The Refinery
 */

namespace refinery\courier;

// use refinery\courier\services\CourierService as CourierServiceService;
use refinery\courier\services\EventsService as EventsService;
use refinery\courier\variables\CourierVariable;
use refinery\courier\twigextensions\CourierTwigExtension;
use refinery\courier\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;
use yii\log\Logger;

/**
 * Class Courier
 *
 * @author    The Refinery
 * @package   Courier
 * @since     0.1.0
 *
 * @property  CourierServiceService $courierService
 */
class Courier extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Courier
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.1.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Craft3 removed the ability to log plugin-level stuff to a different
        // file other than web.log. The following setup will allow you to call
        // Courier::log() from anywhere in the plugin and it will write to
        // @storage/logs/courier.log as well as the normal web.log
        $fileTarget = new \craft\log\FileTarget([
            'logFile' => Craft::getAlias('@storage/logs/courier.log'),
            'categories' => ['courier']
        ]);

        Craft::getLogger()->dispatcher->targets[] = $fileTarget;
        // ---------------------------------------------------------------------

        $this->setComponents(
            [
                'events' => services\Events::class,
                'blueprints' => services\Blueprints::class,
                'deliveries' => services\Deliveries::class,
                'emails' => services\Emails::class,
                'modelPopulator' => services\ModelPopulator::class,
            ]
        );

        if($this->isInstalled){
            $this->events->setupEventListeners();
        }

        // 'courier' => [ 'action' => 'courier/blueprints/index' ],
        // 'courier/blueprints' => [ 'action' => 'courier/blueprints/index' ],
        // 'courier/blueprints/new' => [ 'action' => 'courier/blueprints/create' ],
        // 'courier/blueprints/(?P<id>\d+)' => [ 'action' => 'courier/blueprints/edit' ],
        // 'courier/deliveries' => [ 'action' => 'courier/deliveries/index' ],

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['courier'] = 'courier/blueprints/index';
            $event->rules['courier/blueprints'] = 'courier/blueprints/index';
            $event->rules['courier/blueprints/new'] = 'courier/blueprints/create';
            $event->rules['courier/blueprints/<id:\d+>'] = 'courier/blueprints/edit';
            $event->rules['courier/deliveries'] = 'courier/deliveries/index';
            $event->rules['courier/events'] = 'courier/events/index';
            $event->rules['courier/events/new'] = 'courier/events/create';
            $event->rules['courier/events/<id:\d+>'] = 'courier/events/edit';
        });
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'courier/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    // Craft3 removed the ability to log plugin-level stuff to a different
    // file other than web.log. This function allows you to call
    // Courier::log() from anywhere in the plugin and it will write to
    // @storage/logs/courier.log as well as the normal web.log
    // See $this->init() for more details.
    public static function log($message, $level = Logger::LEVEL_INFO){
        Craft::getLogger()
            ->log(
                $message,
                $level,
                'courier'
            );
    }
}
