<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use Exception;
use OCP\IL10N;
use OCP\Notification\INotification;

/**
 * Class UpgradeRequiredNotification
 *
 * @package OCA\Passwords\Notification
 */
class BreachedPasswordsUpdateFailedNotification extends AbstractNotification {

    const NAME = 'breached_passwords_update_failed';
    const TYPE = 'errors';
    const MANUAL_URL_BREACHED_PASSWORDS_UPDATE_FAILED = 'https://git.mdns.eu/nextcloud/passwords/-/wikis/Administrators/Notifications/Breached-Passwords-Update-Failure-Notification';

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     *
     * @throws Exception
     */
    public function send(string $userId, array $parameters = []): void {
        $notification
            = $this->createNotification($userId)
                   ->setSubject(self::NAME, $parameters)
                   ->setObject('error', 'exception');

        $this->addRawLink($notification, self::MANUAL_URL_BREACHED_PASSWORDS_UPDATE_FAILED);

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

        $this->processLink($notification, self::MANUAL_URL_BREACHED_PASSWORDS_UPDATE_FAILED, $localisation->t('More information'));

        return $notification
            ->setParsedSubject($localisation->t('Could not update breached passwords database'))
            ->setParsedMessage($localisation->t(
                'The breached passwords database update failed three times. Please consult the manual and check your logs. Reason: %1$s',
                [$parameters['reason'] ?? '?']
            ))
            ->setLink(self::MANUAL_URL_BREACHED_PASSWORDS_UPDATE_FAILED);
    }
}