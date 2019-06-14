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
 * Class BadPasswordNotification
 *
 * @package OCA\Passwords\Notification
 */
class BadPasswordNotification extends AbstractNotification {

    const NAME = 'bad_password';
    const TYPE = 'security';

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     */
    public function send(string $userId, array $parameters = []): void {
        $parameters['count'] = isset($parameters['count']) && $parameters['count'] > 0 ? $parameters['count']:1;

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

        $link    = $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/security/2';
        $title   = $this->getTitle($localisation, $parameters['count']);
        $message = $this->getMessage($localisation, $parameters['count']);

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink($link);
    }

    /**
     * @param IL10N $localisation
     * @param int   $count
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation, int $count): string {
        return
            $localisation->n(
                'One of your passwords is no longer secure.',
                'Some of your passwords are no longer secure.',
                $count
            )
            .' '.
            $localisation->n(
                'Open the passwords app to change it.',
                'Open the passwords app to change them.',
                $count
            );
    }

    /**
     * @param IL10N $localisation
     * @param int   $count
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, int $count): string {
        return
            $localisation->t('Passwords regularly checks if your passwords have been compromised by a data breach.')
            .' '.
            $localisation->n(
                'This security check has found that one of your passwords is insecure.',
                'This security check has found that %s of your passwords are insecure.',
                $count, [$count]
            );
    }
}