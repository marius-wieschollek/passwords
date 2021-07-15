<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Notification;

use Exception;
use OCP\IL10N;
use OCP\Notification\INotification;

class BackupFailedNotification extends AbstractNotification {

    const NAME = 'backup_autorestore_fail';
    const TYPE = 'errors';

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     *
     * @throws Exception
     */
    public function send(string $userId, array $parameters = []): void {
        $notification = $this
            ->createNotification($userId)
            ->setSubject(self::NAME, $parameters)
            ->setObject('warning', 'backup_autorestore_fail');
        $this->addRawLink($notification, $this->getLink());

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
        $backup = $notification->getSubjectParameters()['backup'];

        $link    = $this->getLink();
        $title   = $localisation->t('Automatic backup restore failed');
        $message = $this->getMessage($localisation, $backup);
        $this->processLink($notification, $link, $localisation->t('Open passwords'));

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink($link);
    }

    /**
     * @param IL10N  $localisation
     * @param string $backup
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, string $backup): string {
        return $localisation->t('The passwords app seems tho have encountered a database issue.')
               .' '.
               $localisation->t('The attempt to restore the app backup "%s" failed.', [$backup])
               .' '.
               $localisation->t('Manual intervention may be required.')
               .' '.
               $localisation->t('You can disable automatic backup restoring in the app settings.');
    }

    /**
     * @return string
     */
    protected function getLink(): string {
        return $this->urlGenerator->linkToRouteAbsolute('passwords.page.index');
    }
}