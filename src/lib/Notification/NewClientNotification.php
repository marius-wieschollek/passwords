<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use OCA\Passwords\Helper\Settings\ThemeSettingsHelper;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class NewClientNotification
 *
 * @package OCA\Passwords\Notification
 */
class NewClientNotification extends AbstractNotification {

    const NAME = 'new_client';
    const TYPE = 'security';

    /**
     * @var ThemeSettingsHelper
     */
    protected $themingSettings;

    /**
     * NewClientNotification constructor.
     *
     * @param IFactory            $l10nFactory
     * @param IURLGenerator       $urlGenerator
     * @param IManager            $notificationManager
     * @param ThemeSettingsHelper $themingSettings
     */
    public function __construct(IFactory $l10nFactory, IURLGenerator $urlGenerator, IManager $notificationManager, ThemeSettingsHelper $themingSettings) {
        parent::__construct($l10nFactory, $urlGenerator, $notificationManager);
        $this->themingSettings = $themingSettings;
    }

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     */
    public function send(string $userId, array $parameters = []): void {
        $notification
            = $this->createNotification($userId)
                   ->setSubject(self::NAME, $parameters)
                   ->setObject('object', 'password');
        $this->notificationManager->notify($notification);
    }

    /**
     * Process the notification for display
     *
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    public function process(INotification $notification, IL10N $localisation): INotification {
        $parameters = $notification->getSubjectParameters();

        $link    = $this->urlGenerator->linkToRouteAbsolute('settings.PersonalSettings.index', ['section' => 'security']);
        $title   = $this->getTitle($localisation);
        $message = $this->getMessage($localisation, $parameters['client']);

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink($link);
    }

    /**
     * @param IL10N $localisation
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation): string {
        return $localisation->t('A new client was added');
    }

    /**
     * @param IL10N  $localisation
     * @param string $client
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, string $client): string {
        return
            $localisation->t('A new client with the name "%s" was connected successfully to Passwords with PassLink.', [$client])
            .' '.
            $localisation->t('You can manage all connected devices and apps in the %s security section.', [$this->themingSettings->get('label')]);
    }
}