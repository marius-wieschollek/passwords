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
 * Class EmptyRequiredSettingNotification
 *
 * @package OCA\Passwords\Notification
 */
class EmptyRequiredSettingNotification extends AbstractNotification {

    const NAME = 'empty_setting';
    const TYPE = 'admin';

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
                   ->setObject('setting', $parameters['setting']);

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
        $setting = $notification->getSubjectParameters()['setting'];

        $title   = $this->getTitle($localisation, $setting);
        $message = $this->getMessage($localisation, $setting);
        $link    = $this->urlGenerator->getAbsoluteURL("/index.php/settings/admin/passwords#passwords-{$setting}");

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink($link);
    }

    /**
     * @param IL10N  $localisation
     * @param string $setting
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation, string $setting): string {
        return $localisation->t("Your {$setting} service configuration is invalid");
    }

    /**
     * @param IL10N  $localisation
     * @param string $setting
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, string $setting): string {
        return $localisation->t("Your chosen {$setting} service requires an api key or endpoint, but none is given.")
            .' '.$localisation->t('Open the admin settings to update the configuration or chose another service.');
    }
}