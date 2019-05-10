<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\SettingsService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class AbstractNotification
 *
 * @package OCA\Passwords\Notification
 */
abstract class AbstractNotification {

    const NAME = self::NAME;

    /**
     * @var IFactory
     */
    protected $l10nFactory;

    /**
     * @var SettingsService
     */
    protected $settings;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var IManager
     */
    protected $notificationManager;

    /**
     * AbstractNotification constructor.
     *
     * @param IFactory        $l10nFactory
     * @param SettingsService $settings
     * @param IURLGenerator   $urlGenerator
     * @param IManager        $notificationManager
     */
    public function __construct(
        IFactory $l10nFactory,
        SettingsService $settings,
        IURLGenerator $urlGenerator,
        IManager $notificationManager
    ) {
        $this->settings            = $settings;
        $this->l10nFactory         = $l10nFactory;
        $this->urlGenerator        = $urlGenerator;
        $this->notificationManager = $notificationManager;
    }

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     */
    abstract public function send(string $userId, array $parameters = []): void;

    /**
     * Process the notification for display
     *
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    abstract public function process(INotification $notification, IL10N $localisation): INotification;

    /**
     * @param string $userId
     *
     * @return INotification
     */
    protected function createNotification(string $userId): INotification {
        $icon = $this->urlGenerator->imagePath(Application::APP_NAME, 'app-dark.svg');

        return $this->notificationManager
            ->createNotification()
            ->setApp(Application::APP_NAME)
            ->setDateTime(new \DateTime())
            ->setUser($userId)
            ->setIcon($icon);
    }
}