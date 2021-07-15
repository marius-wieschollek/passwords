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

class BackupRestoredNotification extends AbstractNotification {

    const NAME = 'backup_autorestore';
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
            ->setObject('warning', 'backup_autorestore');
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
        $title   = $localisation->t('Backup automatically restored');
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
               $localisation->t('Because of this, the app backup "%s" was restored automatically.', [$backup])
               .' '.
               $localisation->t('If this was an error, a backup of the database was made before restoring it.')
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