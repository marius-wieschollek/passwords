<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use OCP\IL10N;
use OCP\Notification\INotification;

/**
 * Class UpgradeRequiredNotification
 *
 * @package OCA\Passwords\Notification
 */
class UpgradeRequiredNotification extends AbstractNotification {

    const NAME                           = 'upgrade_required';
    const TYPE                           = 'admin';
    const MANUAL_URL_SYSTEM_REQUIREMENTS = 'https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/Notifications/Platform-Support-Notification';

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
                   ->setObject('app', 'requirements');

        $this->addRawLink($notification, self::MANUAL_URL_SYSTEM_REQUIREMENTS);

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
        $title   = $this->getTitle($localisation);
        $message = $this->getMessage($localisation, $notification->getSubjectParameters());
        $this->processLink($notification, self::MANUAL_URL_SYSTEM_REQUIREMENTS, $localisation->t('More information'));

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink(self::MANUAL_URL_SYSTEM_REQUIREMENTS);
    }

    /**
     * @param IL10N $localisation
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation): string {
        return $localisation->t('The passwords app will discontinue updates for your platform');
    }

    /**
     * @param IL10N $localisation
     * @param array $params
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, array $params): string {
        return $localisation->t('Passwords %s will raise the minimum system requirements to Nextcloud %s and PHP %s.', $params).
               ' '.
               $localisation->t('We recommend that you update your server so you don\'t miss out on future updates.').
               ' '.
               $localisation->t('Your installed version will continue to work regardless and you can still use it as it is.');
    }
}