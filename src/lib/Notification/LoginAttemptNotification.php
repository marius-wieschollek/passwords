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
 * Class LoginAttemptNotification
 *
 * @package OCA\Passwords\Notification
 */
class LoginAttemptNotification extends AbstractNotification {

    const NAME = 'user_login_attempts';
    const TYPE = 'security';

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
                   ->setObject('object', 'login');

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
        $title   = $localisation->t('Suspicious amount of failed login attempts detected.');
        $link    = $this->urlGenerator->linkToRouteAbsolute('passwords.page.index');
        $message = $this->getMessage($localisation, $notification->getSubjectParameters());

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink($link);
    }

    /**
     * @param IL10N $localisation
     * @param array $parameters
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, array $parameters): string {
        $message = $localisation->t('We have detected several failed attempts to unlock your password database by "%s".', [$parameters['client']])
                   .' '.
                   $localisation->t('This could indicate that someone is trying to break into your account.')
                   .' ';

        if($parameters['revoked'] === true) {
            $message .= $localisation->t('To prevent further attempts, the API credentials of this client were revoked.')
                        .' '.
                        $localisation->t('Also, password based API authentication has been disabled.')
                        .' '.
                        $localisation->t('If you want to continue using this client, you need to create a new token for it.')
                        .' '.
                        $localisation->t('If you don\'t know this client, please change your password and review your device list.');
        } else {
            $message .= $localisation->t('To prevent further attempts, password based API authentication has been disabled.')
                        .' '.
                        $localisation->t('To enable it again, log in with the webapp or any client using token authentication.')
                        .' '.
                        $localisation->t('To increase security, we recommend using device specific tokens instead of your password.');
        }

        return $message;
    }
}