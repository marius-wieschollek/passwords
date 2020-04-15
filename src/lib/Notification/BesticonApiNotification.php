<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use OCP\IL10N;
use OCP\Notification\IAction;
use OCP\Notification\INotification;

/**
 * Class BesticonApiNotification
 *
 * @package OCA\Passwords\Notification
 */
class BesticonApiNotification extends AbstractNotification {

    const NAME                 = 'besticon';
    const TYPE                 = 'admin';
    const BESTICON_HOSTING_URL = 'https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/Besticon-Self-Hosting';

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
                   ->setObject('besticon', 'api');

        $linkAction = $notification->createAction();
        $linkAction->setLabel('tutorial')
                   ->setLink(self::BESTICON_HOSTING_URL, IAction::TYPE_WEB)
                   ->setPrimary(true);
        $notification->addAction($linkAction);

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
        $title   = $localisation->t('Please consider self-hosting your favicon api');
        $message = $this->getMessage($localisation);

        foreach($notification->getActions() as $action) {
            $action->setLink(self::BESTICON_HOSTING_URL, IAction::TYPE_WEB)->setParsedLabel($localisation->t('Open tutorial'));
            $notification->addParsedAction($action);
        }

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink(self::BESTICON_HOSTING_URL);
    }

    /**
     * @param IL10N $localisation
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation): string {
        return $localisation->t('Your Nextcloud uses our shared Besticon instances to fetch website icons.')
               .' '.$localisation->t('These instances are intended for small Nextcloud setups with very little traffic.')
               .' '.$localisation->t('Your Nextcloud requests significantly more icons than the average.')
               .' '.$localisation->t('Please click the link and follow our easy tutorial to host Besticon yourself.');
    }
}