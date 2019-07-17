<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use InvalidArgumentException;
use OCA\Passwords\AppInfo\Application;
use OCP\Notification\INotification;

/**
 * Class NC17NotificationService
 *
 * @package OCA\Passwords\Services
 * @TODO: Merge with NotificationService in 2020.1
 */
class NC17NotificationService extends NotificationService {

    /**
     * Identifier of the notifier, only use [a-z0-9_]
     *
     * @return string
     * @since 17.0.0
     */
    public function getID(): string {
        return Application::APP_NAME;
    }

    /**
     * Human readable name describing the notifier
     *
     * @return string
     * @since 17.0.0
     */
    public function getName(): string {
        return $this->l10NFactory->get(Application::APP_NAME)->t('Passwords');
    }

    /**
     * @param INotification $notification
     * @param string        $languageCode The code of the language that should be used to prepare the notification
     *
     * @return INotification
     * @throws InvalidArgumentException When the notification was not prepared by a notifier
     * @since 9.0.0
     */
    public function prepare(INotification $notification, string $languageCode) {
        return $this->realPrepare($notification, $languageCode);
    }
}