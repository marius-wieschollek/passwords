<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 26.08.17
 * Time: 17:01
 */

namespace OCA\Passwords\AppInfo;

use OCA\Passwords\Activity;
use OCA\Passwords\Controller\AccessController;
use OCA\Passwords\Controller\AdminSettingsController;
use OCA\Passwords\Controller\Api\PasswordApiController;
use OCA\Passwords\Controller\Api\ServiceApiController;
use OCA\Passwords\Controller\PageController;
use OCA\Passwords\Db\PasswordMapper;
use OCA\Passwords\Db\RevisionMapper;
use OCA\Passwords\Helper\PasswordApiObjectHelper;
use OCA\Passwords\Helper\PasswordGenerationHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\PageShotService;
use OCA\Passwords\Services\PasswordGenerationService;
use OCA\Passwords\Services\PasswordService;
use OCA\Passwords\Services\RevisionService;
use OCA\Passwords\Services\ValidationService;
use OCA\Passwords\Settings\AdminSettings;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\Files\IAppData;

/**
 * Class Application
 *
 * @package OCA\Passwords\AppInfo
 */
class Application extends App {

    const APP_NAME = 'passwords';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_NAME, $urlParams);

        $this->registerPersonalSettings();
        //$this->registerActivities();
        $this->registerServices();
    }

    /**
     *
     */
    protected function registerActivities(): void {
        \OC::$server->getActivityManager()->registerExtension(function () {
            return new Activity(
                \OC::$server->query('L10NFactory'),
                \OC::$server->getURLGenerator(),
                \OC::$server->getActivityManager()
            );
        });
    }

    /**
     *
     */
    protected function registerPersonalSettings(): void {
        \OCP\App::registerPersonal('passwords', 'personal/index');
    }

    /**
     *
     */
    protected function registerServices(): void {
        $container = $this->getContainer();

        /**
         * Controllers
         */
        $container->registerService('PageController', function (IAppContainer $c) {
            return new PageController(
                $c->query('AppName'),
                $c->query('Request')
            );
        });

        $container->registerService('AccessController', function (IAppContainer $c) {
            return new AccessController(
                $c->query('AppName'),
                $c->query('Request'),

                // inject the URLGenerator into the page controller
                $c->query('ServerContainer')->getURLGenerator()
            );
        });

        $container->registerService('PasswordApiController', function (IAppContainer $c) {
            return new PasswordApiController(
                $c->query('AppName'),
                $c->query('Request'),
                $c->query('PasswordService'),
                $c->query('RevisionService'),
                $c->query('PasswordApiObjectHelper')
            );
        });

        $container->registerService('ServiceApiController', function (IAppContainer $c) {
            return new ServiceApiController(
                $c->query('AppName'),
                $c->query('Request'),
                $c->query('FaviconService'),
                $c->query('PageShotService'),
                $c->query('PasswordGenerationService')
            );
        });

        $container->registerService('AdminSettingsController', function (IAppContainer $c) {
            return new AdminSettingsController(
                $c->query('AppName'),
                $c->query('Request'),
                $this->getContainer()->getServer()->getConfig(),
                clone $c->query('FileCacheService')
            );
        });

        /**
         * Mappers
         */
        $container->registerService('PasswordMapper', function (IAppContainer $c) {
            $server = $this->getContainer()->getServer();

            return new PasswordMapper(
                $server->getDatabaseConnection(),
                $server->getUserSession()->getUser()->getUID()
            );
        });

        $container->registerService('RevisionMapper', function (IAppContainer $c) {
            $server = $this->getContainer()->getServer();

            return new RevisionMapper(
                $server->getDatabaseConnection(),
                $server->getUserSession()->getUser()->getUID()
            );
        });

        /**
         * Services
         */
        $container->registerService('PasswordService', function (IAppContainer $c) {
            $server = $this->getContainer()->getServer();

            return new PasswordService(
                $server->getUserSession()->getUser(),
                $c->query('PasswordMapper')
            );
        });
        $container->registerService('RevisionService', function (IAppContainer $c) {
            $server = $this->getContainer()->getServer();

            return new RevisionService(
                $server->getUserSession()->getUser(),
                $c->query('ValidationService'),
                $c->query('EncryptionService'),
                $c->query('RevisionMapper')
            );
        });
        $container->registerService('FileCacheService', function (IAppContainer $c) {
            return new FileCacheService(
                $c->query('AppData')
            );
        });
        $container->registerService('FaviconService', function (IAppContainer $c) {
            return new FaviconService(
                $c->query('ConfigurationService'),
                clone $c->query('FileCacheService')
            );
        });
        $container->registerService('PageShotService', function (IAppContainer $c) {
            return new PageShotService(
                $c->query('ConfigurationService'),
                clone $c->query('FileCacheService')
            );
        });
        $container->registerService('ConfigurationService', function (IAppContainer $c) {
            $server = $this->getContainer()->getServer();

            return new ConfigurationService(
                $server->getUserSession()->getUser()->getUID(),
                $server->getConfig()
            );
        });

        $container->registerService('LocalisationService', function (IAppContainer $c) {
            return $c->query('ServerContainer')->getL10N(self::APP_NAME);
        });

        /**
         * Helper
         */
        $container->registerService('PasswordApiObjectHelper', function (IAppContainer $c) {
            return new PasswordApiObjectHelper(
                $c->query('RevisionService')
            );
        });

        /**
         * Admin Settings
         */
        $container->registerService('AdminSettings', function (IAppContainer $c) {
            return new AdminSettings(
                $c->query('LocalisationService'),
                $c->query('ConfigurationService'),
                clone $c->query('FileCacheService')
            );
        });

        /**
         * Alias
         */
        $container->registerAlias('AppData', IAppData::class);
        $container->registerAlias('ValidationService', ValidationService::class);
        $container->registerAlias('EncryptionService', EncryptionService::class);
        $container->registerAlias('PasswordGenerationService', PasswordGenerationService::class);
    }

}