<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\AppInfo;

use OCA\Passwords\Controller\Actions\RecoverHiddenController;
use OCA\Passwords\Controller\Admin\CacheController;
use OCA\Passwords\Controller\Admin\SettingsController;
use OCA\Passwords\Controller\User\SettingsController as UserSettingsController;
use OCA\Passwords\Controller\Api\AccountApiController;
use OCA\Passwords\Controller\Api\FolderApiController;
use OCA\Passwords\Controller\Api\KeychainApiController;
use OCA\Passwords\Controller\Api\PasswordApiController;
use OCA\Passwords\Controller\Api\ServiceApiController;
use OCA\Passwords\Controller\Api\SessionApiController;
use OCA\Passwords\Controller\Api\SettingsApiController;
use OCA\Passwords\Controller\Api\ShareApiController;
use OCA\Passwords\Controller\Api\TagApiController;
use OCA\Passwords\Controller\Link\ConnectController;
use OCA\Passwords\Dashboard\PasswordsWidget;
use OCA\Passwords\EventListener\Challenge\ChallengeActivatedListener;
use OCA\Passwords\EventListener\CSP\AddCSPListener;
use OCA\Passwords\EventListener\Folder\BeforeFolderDeletedListener;
use OCA\Passwords\EventListener\Folder\BeforeFolderSetRevisionListener;
use OCA\Passwords\EventListener\Folder\FolderClonedListener;
use OCA\Passwords\EventListener\Folder\FolderDeletedListener;
use OCA\Passwords\EventListener\Password\BeforePasswordDeletedListener;
use OCA\Passwords\EventListener\Password\BeforePasswordSetRevisionListener;
use OCA\Passwords\EventListener\Password\PasswordClonedListener;
use OCA\Passwords\EventListener\Password\PasswordDeletedListener;
use OCA\Passwords\EventListener\PasswordRevision\BeforePasswordRevisionSavedEventListener;
use OCA\Passwords\EventListener\Share\ShareDeletedListener;
use OCA\Passwords\EventListener\Tag\BeforeTagDeletedListener;
use OCA\Passwords\EventListener\Tag\BeforeTagSetRevisionListener;
use OCA\Passwords\EventListener\Tag\TagClonedListener;
use OCA\Passwords\EventListener\Tag\TagDeletedListener;
use OCA\Passwords\EventListener\User\BeforeUserCreatedListener;
use OCA\Passwords\EventListener\User\UserDeletedListener;
use OCA\Passwords\EventListener\User\UserPasswordChangedListener;
use OCA\Passwords\Events\Challenge\BeforeChallengeActivatedEvent;
use OCA\Passwords\Events\Challenge\ChallengeActivatedEvent;
use OCA\Passwords\Events\Folder\BeforeFolderDeletedEvent;
use OCA\Passwords\Events\Folder\BeforeFolderSetRevisionEvent;
use OCA\Passwords\Events\Folder\FolderClonedEvent;
use OCA\Passwords\Events\Folder\FolderDeletedEvent;
use OCA\Passwords\Events\Password\BeforePasswordDeletedEvent;
use OCA\Passwords\Events\Password\BeforePasswordSetRevisionEvent;
use OCA\Passwords\Events\Password\PasswordClonedEvent;
use OCA\Passwords\Events\Password\PasswordDeletedEvent;
use OCA\Passwords\Events\PasswordRevision\BeforePasswordRevisionCreatedEvent;
use OCA\Passwords\Events\PasswordRevision\BeforePasswordRevisionUpdatedEvent;
use OCA\Passwords\Events\Share\ShareDeletedEvent;
use OCA\Passwords\Events\Tag\BeforeTagDeletedEvent;
use OCA\Passwords\Events\Tag\BeforeTagSetRevisionEvent;
use OCA\Passwords\Events\Tag\TagClonedEvent;
use OCA\Passwords\Events\Tag\TagDeletedEvent;
use OCA\Passwords\Helper\Sharing\ShareUserListHelper;
use OCA\Passwords\Helper\Words\SpecialCharacterHelper;
use OCA\Passwords\Middleware\ApiSecurityMiddleware;
use OCA\Passwords\Middleware\ApiSessionMiddleware;
use OCA\Passwords\Provider\Favicon\BestIconProvider;
use OCA\Passwords\Provider\Favicon\DefaultFaviconProvider;
use OCA\Passwords\Provider\Favicon\DuckDuckGoProvider;
use OCA\Passwords\Provider\Favicon\FaviconGrabberProvider;
use OCA\Passwords\Provider\Favicon\FaviconProviderInterface;
use OCA\Passwords\Provider\Favicon\GoogleFaviconProvider;
use OCA\Passwords\Provider\Favicon\LocalFaviconProvider;
use OCA\Passwords\Provider\Preview\BrowshotPreviewProvider;
use OCA\Passwords\Provider\Preview\DefaultPreviewProvider;
use OCA\Passwords\Provider\Preview\PageresCliProvider;
use OCA\Passwords\Provider\Preview\PreviewProviderInterface;
use OCA\Passwords\Provider\Preview\ScreeenlyProvider;
use OCA\Passwords\Provider\Preview\ScreenShotLayerProvider;
use OCA\Passwords\Provider\Preview\ScreenShotMachineProvider;
use OCA\Passwords\Provider\SecurityCheck\BigDbPlusHibpSecurityCheckProvider;
use OCA\Passwords\Provider\SecurityCheck\BigLocalDbSecurityCheckProvider;
use OCA\Passwords\Provider\SecurityCheck\HaveIBeenPwnedProvider;
use OCA\Passwords\Provider\SecurityCheck\SecurityCheckProviderInterface;
use OCA\Passwords\Provider\SecurityCheck\SmallLocalDbSecurityCheckProvider;
use OCA\Passwords\Provider\Words\AutoWordsProvider;
use OCA\Passwords\Provider\Words\LeipzigCorporaProvider;
use OCA\Passwords\Provider\Words\LocalWordsProvider;
use OCA\Passwords\Provider\Words\RandomCharactersProvider;
use OCA\Passwords\Provider\Words\SnakesWordsProvider;
use OCA\Passwords\Provider\Words\WordsProviderInterface;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\NotificationService;
use OCA\Passwords\SetupChecks\BackgroundJobsExecutedWithCronSetupCheck;
use OCA\Passwords\SetupChecks\BackgroundJobsPhpVersionSetupCheck;
use OCA\Passwords\UserMigration\PasswordsMigrator;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Http\Client\IClientService;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use OCP\Share\IManager as ShareManager;
use OCP\User\Events\BeforeUserCreatedEvent;
use OCP\User\Events\CreateUserEvent;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserDeletedEvent;
use Psr\Container\ContainerInterface;
use Random\Randomizer;

/**
 * Class Application
 *
 * @package OCA\Passwords\AppInfo
 */
class Application extends App implements IBootstrap {

    const APP_NAME = 'passwords';

    /**
     * Application constructor.
     *
     * @param array $urlParams
     */
    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_NAME, $urlParams);
    }

    /**
     * @param IRegistrationContext $context
     */
    public function register(IRegistrationContext $context): void {
        $this->registerDiClasses($context);
        $this->registerSystemHooks();
        $this->registerMiddleware($context);
        $context->registerUserMigrator(PasswordsMigrator::class);
        $context->registerDashboardWidget(PasswordsWidget::class);
    }

    /**
     * @param IBootContext $context
     */
    public function boot(IBootContext $context): void {
        $this->registerNotificationNotifier();
        $this->registerInternalListeners();
    }

    /**
     * @param IRegistrationContext $context
     */
    protected function registerDiClasses(IRegistrationContext $context): void {
        /**
         * Controllers
         */
        $this->registerController($context);

        /**
         * Helper
         */
        $this->registerHelpers($context);

        /**
         * Providers
         */
        $this->registerProviders($context);

        /**
         * Setup Checks
         */
        $this->registerSetupChecks($context);
    }

    /**
     * @param IRegistrationContext $context
     */
    protected function registerMiddleware(IRegistrationContext $context): void {
        $context->registerServiceAlias('ApiSecurityMiddleware', ApiSecurityMiddleware::class);
        $context->registerMiddleware('ApiSecurityMiddleware');

        $context->registerServiceAlias('ApiSessionMiddleware', ApiSessionMiddleware::class);
        $context->registerMiddleware('ApiSessionMiddleware');
    }

    /**
     * @param IRegistrationContext $context
     */
    protected function registerController(IRegistrationContext $context): void {
        $context->registerServiceAlias('AdminSettingsController', SettingsController::class);
        $context->registerServiceAlias('AdminCachesController', CacheController::class);
        $context->registerServiceAlias('UserSettingsController', UserSettingsController::class);
        $context->registerServiceAlias('KeychainApiController', KeychainApiController::class);
        $context->registerServiceAlias('PasswordApiController', PasswordApiController::class);
        $context->registerServiceAlias('SettingsApiController', SettingsApiController::class);
        $context->registerServiceAlias('AccountApiController', AccountApiController::class);
        $context->registerServiceAlias('SessionApiController', SessionApiController::class);
        $context->registerServiceAlias('ServiceApiController', ServiceApiController::class);
        $context->registerServiceAlias('FolderApiController', FolderApiController::class);
        $context->registerServiceAlias('ShareApiController', ShareApiController::class);
        $context->registerServiceAlias('ConnectController', ConnectController::class);
        $context->registerServiceAlias('TagApiController', TagApiController::class);
        $context->registerServiceAlias('RecoverHiddenController', RecoverHiddenController::class);
    }

    /**
     *
     */
    protected function registerInternalListeners() {
        /* @var IEventDispatcher $eventDispatcher */
        $dispatcher = $this->getContainer()->get(IEventDispatcher::class);
        $dispatcher->addServiceListener(BeforePasswordDeletedEvent::class, BeforePasswordDeletedListener::class);
        $dispatcher->addServiceListener(BeforePasswordSetRevisionEvent::class, BeforePasswordSetRevisionListener::class);
        $dispatcher->addServiceListener(PasswordClonedEvent::class, PasswordClonedListener::class);
        $dispatcher->addServiceListener(PasswordDeletedEvent::class, PasswordDeletedListener::class);

        $dispatcher->addServiceListener(BeforePasswordRevisionCreatedEvent::class, BeforePasswordRevisionSavedEventListener::class);
        $dispatcher->addServiceListener(BeforePasswordRevisionUpdatedEvent::class, BeforePasswordRevisionSavedEventListener::class);

        $dispatcher->addServiceListener(BeforeFolderDeletedEvent::class, BeforeFolderDeletedListener::class);
        $dispatcher->addServiceListener(BeforeFolderSetRevisionEvent::class, BeforeFolderSetRevisionListener::class);
        $dispatcher->addServiceListener(FolderClonedEvent::class, FolderClonedListener::class);
        $dispatcher->addServiceListener(FolderDeletedEvent::class, FolderDeletedListener::class);

        $dispatcher->addServiceListener(BeforeTagDeletedEvent::class, BeforeTagDeletedListener::class);
        $dispatcher->addServiceListener(BeforeTagSetRevisionEvent::class, BeforeTagSetRevisionListener::class);
        $dispatcher->addServiceListener(TagClonedEvent::class, TagClonedListener::class);
        $dispatcher->addServiceListener(TagDeletedEvent::class, TagDeletedListener::class);

        $dispatcher->addServiceListener(ShareDeletedEvent::class, ShareDeletedListener::class);

        $dispatcher->addServiceListener(BeforeChallengeActivatedEvent::class, ChallengeActivatedListener::class);
        $dispatcher->addServiceListener(ChallengeActivatedEvent::class, ChallengeActivatedListener::class);
    }

    /**
     *
     */
    protected function registerSystemHooks(): void {
        /* @var IEventDispatcher $eventDispatcher */
        $dispatcher = $this->getContainer()->get(IEventDispatcher::class);
        $dispatcher->addServiceListener(CreateUserEvent::class, BeforeUserCreatedListener::class);
        $dispatcher->addServiceListener(BeforeUserCreatedEvent::class, BeforeUserCreatedListener::class);
        $dispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedListener::class);
        $dispatcher->addServiceListener(PasswordUpdatedEvent::class, UserPasswordChangedListener::class);
        $dispatcher->addServiceListener(AddContentSecurityPolicyEvent::class, AddCSPListener::class);
    }

    /**
     * Registers the Notification service
     */
    protected function registerNotificationNotifier(): void {
        $this->getContainer()->get(IManager::class)->registerNotifierService(NotificationService::class);
    }

    /**
     * @param IRegistrationContext $context
     *
     * @return void
     */
    protected function registerHelpers(IRegistrationContext $context): void {
        $context->registerService(
            LocalWordsProvider::class,
            function (ContainerInterface $c) {
                return new LocalWordsProvider(
                    $c->get(SpecialCharacterHelper::class),
                    $c->get(IFactory::class)->get('core')->getLanguageCode()
                );
            }
        );

        $context->registerService(
            RandomCharactersProvider::class,
            function (ContainerInterface $c) {
                return new RandomCharactersProvider(
                    $c->get(Randomizer::class),
                    $c->get(IFactory::class)->get('core')->getLanguageCode()
                );
            }
        );

        $context->registerService(
            LeipzigCorporaProvider::class,
            function (ContainerInterface $c) {
                return new LeipzigCorporaProvider(
                    $c->get(SpecialCharacterHelper::class),
                    $c->get(IClientService::class),
                    $c->get(Randomizer::class),
                    $c->get(IFactory::class)->get('core')->getLanguageCode()
                );
            }
        );

        $context->registerService(
            ShareUserListHelper::class,
            function (ContainerInterface $c) {
                return new ShareUserListHelper(
                    $c->get(ShareManager::class),
                    $c->get(IUserManager::class),
                    $c->get(IGroupManager::class),
                    $c->get(ConfigurationService::class),
                    $c->get(EnvironmentService::class)
                );
            }
        );
    }

    /**
     * @param IRegistrationContext $context
     *
     * @return void
     */
    protected function registerProviders(IRegistrationContext $context): void {
        $context->registerService(
            SecurityCheckProviderInterface::class,
            function (ContainerInterface $c) {
                $service = $c->get(ConfigurationService::class)->getAppValue('service/security', HelperService::SECURITY_HIBP);

                return match ($service) {
                    HelperService::SECURITY_BIG_LOCAL => $c->get(BigLocalDbSecurityCheckProvider::class),
                    HelperService::SECURITY_SMALL_LOCAL => $c->get(SmallLocalDbSecurityCheckProvider::class),
                    HelperService::SECURITY_BIGDB_HIBP => $c->get(BigDbPlusHibpSecurityCheckProvider::class),
                    default => $c->get(HaveIBeenPwnedProvider::class),
                };
            }
        );

        $context->registerService(
            WordsProviderInterface::class,
            function (ContainerInterface $c) {
                $service = $c->get(ConfigurationService::class)->getAppValue('service/words', HelperService::WORDS_AUTO);

                return match ($service) {
                    HelperService::WORDS_LOCAL => $c->get(LocalWordsProvider::class),
                    HelperService::WORDS_LEIPZIG => $c->get(LeipzigCorporaProvider::class),
                    HelperService::WORDS_SNAKES => $c->get(SnakesWordsProvider::class),
                    HelperService::WORDS_RANDOM => $c->get(RandomCharactersProvider::class),
                    default => $c->get(AutoWordsProvider::class),
                };
            }
        );

        $context->registerService(
            FaviconProviderInterface::class,
            function (ContainerInterface $c) {
                $service = $c->get(ConfigurationService::class)->getAppValue('service/favicon', HelperService::FAVICON_DEFAULT);

                return match ($service) {
                    HelperService::FAVICON_BESTICON => $c->get(BestIconProvider::class),
                    HelperService::FAVICON_FAVICON_GRABBER => $c->get(FaviconGrabberProvider::class),
                    HelperService::FAVICON_DUCK_DUCK_GO => $c->get(DuckDuckGoProvider::class),
                    HelperService::FAVICON_GOOGLE => $c->get(GoogleFaviconProvider::class),
                    HelperService::FAVICON_LOCAL => $c->get(LocalFaviconProvider::class),
                    default => $c->get(DefaultFaviconProvider::class),
                };
            }
        );

        $context->registerService(
            PreviewProviderInterface::class,
            function (ContainerInterface $c) {
                $service = $c->get(ConfigurationService::class)->getAppValue('service/preview', HelperService::PREVIEW_DEFAULT);

                return match ($service) {
                    HelperService::PREVIEW_PAGERES => $c->get(PageresCliProvider::class),
                    HelperService::PREVIEW_BROW_SHOT => $c->get(BrowshotPreviewProvider::class),
                    HelperService::PREVIEW_SCREEN_SHOT_LAYER => $c->get(ScreenShotLayerProvider::class),
                    HelperService::PREVIEW_SCREEN_SHOT_MACHINE => $c->get(ScreenShotMachineProvider::class),
                    HelperService::PREVIEW_SCREEENLY => $c->get(ScreeenlyProvider::class),
                    default => $c->get(DefaultPreviewProvider::class),
                };
            }
        );
    }

    /**
     * @param IRegistrationContext $context
     *
     * @return void
     */
    protected function registerSetupChecks(IRegistrationContext $context): void {
        $context->registerSetupCheck(BackgroundJobsPhpVersionSetupCheck::class);
        $context->registerSetupCheck(BackgroundJobsExecutedWithCronSetupCheck::class);
    }
}