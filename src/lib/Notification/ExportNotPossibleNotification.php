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

namespace OCA\Passwords\Notification;

use Exception;
use OCP\IL10N;
use OCP\Notification\INotification;

class ExportNotPossibleNotification extends AbstractNotification {

    const string NAME = 'user_export_not_possible';
    const string TYPE = 'errors';

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
                   ->setObject('object', 'login');
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
        $link    = $this->getLink();
        $title   = $localisation->t('Passwords can\'t be exported');
        $message = $this->getMessage($localisation, $notification->getSubjectParameters());
        $this->processLink($notification, $link, $localisation->t('Export passwords manually'));

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
        return $localisation->t('Because you have End-to-End encryption enabled, your passwords can\'t be added to the export automatically. As an alternative, you can use the export function within the app to export your data.');
    }

    /**
     * @return string
     */
    protected function getLink(): string {
        return $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/backup/export';
    }
}