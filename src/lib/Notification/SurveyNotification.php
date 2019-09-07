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
 * Class SurveyNotification
 *
 * @package OCA\Passwords\Notification
 */
class SurveyNotification extends AbstractNotification {

    const NAME = 'survey';
    const TYPE = 'admin';

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     */
    public function send(string $userId, array $parameters = []): void {
        $notification = $this->createNotification($userId)
                             ->setSubject(self::NAME, $parameters)
                             ->setObject('admin', 'survey');

        $actionNoUrl  = $this->urlGenerator->linkToRouteAbsolute('passwords.notification.survey', ['answer' => 'yes']);
        $enableAction = $notification->createAction();
        $enableAction->setLabel('enable')
                     ->setLink($actionNoUrl, 'GET')
                     ->setPrimary(true);
        $notification->addAction($enableAction);

        $actionYesUrl  = $this->urlGenerator->linkToRouteAbsolute('passwords.notification.survey', ['answer' => 'no']);
        $disableAction = $notification->createAction();
        $disableAction->setLabel('disable')
                      ->setLink($actionYesUrl, 'GET')
                      ->setPrimary(false);
        $notification->addAction($disableAction);

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
        $title   = $localisation->t('Help us to improve Passwords');
        $message = $this->getMessage($localisation);
        $link    = $this->urlGenerator->linkToRouteAbsolute('settings.AdminSettings.index', ['section' => 'passwords']).'#passwords-server-survey';

        foreach($notification->getActions() as $action) {
            if($action->getLabel() === 'disable') {
                $actionUrl = $this->urlGenerator->linkToRouteAbsolute('passwords.notification.survey', ['answer' => 'no']);
                $action->setLink($actionUrl, 'GET')->setParsedLabel($localisation->t('Not yet'));
            } else if($action->getLabel() === 'enable') {
                $actionUrl = $this->urlGenerator->linkToRouteAbsolute('passwords.notification.survey', ['answer' => 'yes']);
                $action->setLink($actionUrl, 'GET')->setParsedLabel($localisation->t('Participate'));
            }
            $notification->addParsedAction($action);
        }

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
    protected function getMessage(IL10N $localisation): string {
        return $localisation->t('You can help us to improve the Passwords app by participating in our server survey.')
               .' '.
               $localisation->t('This will send us some anonymised data about your setup and selected settings.')
               .' '.
               $localisation->t('You can change this at any time in the app settings.');
    }
}