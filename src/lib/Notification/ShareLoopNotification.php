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
 * Class ShareLoopNotification
 *
 * @package OCA\Passwords\Notification
 */
class ShareLoopNotification extends AbstractNotification {

    const NAME = 'share_loop';
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
        $notification
            = $this->createNotification($userId)
                   ->setSubject(self::NAME, $parameters)
                   ->setObject('share', 'loop');
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
        $count = $notification->getSubjectParameters()['passwords'];

        $link    = $this->getLink();
        $title   = $this->getTitle($localisation, $count);
        $message = $this->getMessage($localisation);
        $this->processLink($notification, $link, $localisation->t('View shared passwords'));

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
                'One of your passwords could not be shared because the recipient already has access to it.',
                '%s of your passwords could not be shared because the recipient already has access to them.',
                $count,
                [$count]
            );
    }

    /**
     * @param IL10N $localisation
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation): string {
        return $localisation->t('Sharing a password that has been shared with you can sometimes create a loop.')
               .' '.
               $localisation->t('To prevent this, these passwords will not be shared.');
    }

    /**
     * @return string
     */
    protected function getLink(): string {
        return $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/shared/1';
    }
}