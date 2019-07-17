<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use InvalidArgumentException;
use OCP\Notification\INotification;

/**
 * Class NC16NotificationService
 *
 * @package OCA\Passwords\Services
 * @TODO: Remove in 2020.1
 */
class NC16NotificationService extends NotificationService {
    /**
     * @param INotification $notification
     * @param string        $languageCode The code of the language that should be used to prepare the notification
     *
     * @return INotification
     * @throws InvalidArgumentException When the notification was not prepared by a notifier
     * @since 9.0.0
     */
    public function prepare(INotification $notification, $languageCode = 'en_US') {
        return $this->realPrepare($notification, (string) $languageCode);
    }
}