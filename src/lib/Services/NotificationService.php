<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Notification\AbstractNotification;
use OCA\Passwords\Notification\BadPasswordNotification;
use OCA\Passwords\Notification\ImpersonationNotification;
use OCA\Passwords\Notification\ShareCreatedNotification;
use OCA\Passwords\Notification\ShareLoopNotification;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

/**
 * Class NotificationService
 *
 * @package OCA\Passwords\Notification
 */
class NotificationService implements INotifier {

    /**
     * @var SettingsService
     */
    protected $settings;

    /**
     * @var IFactory
     */
    protected $l10NFactory;

    /**
     * @var ShareLoopNotification
     */
    protected $shareLoopNotification;

    /**
     * @var ShareCreatedNotification
     */
    protected $shareCreatedNotification;

    /**
     * @var BadPasswordNotification
     */
    protected $badPasswordNotification;

    /**
     * @var ImpersonationNotification
     */
    protected $impersonationNotification;

    /**
     * NotificationService constructor.
     *
     * @param IFactory                  $l10nFactory
     * @param SettingsService           $settings
     * @param ShareLoopNotification     $shareLoopNotification
     * @param BadPasswordNotification   $badPasswordNotification
     * @param ShareCreatedNotification  $shareCreatedNotification
     * @param ImpersonationNotification $impersonationNotification
     */
    public function __construct(
        IFactory $l10nFactory,
        SettingsService $settings,
        ShareLoopNotification $shareLoopNotification,
        BadPasswordNotification $badPasswordNotification,
        ShareCreatedNotification $shareCreatedNotification,
        ImpersonationNotification $impersonationNotification
    ) {
        $this->settings                  = $settings;
        $this->l10NFactory               = $l10nFactory;
        $this->shareLoopNotification     = $shareLoopNotification;
        $this->badPasswordNotification   = $badPasswordNotification;
        $this->shareCreatedNotification  = $shareCreatedNotification;
        $this->impersonationNotification = $impersonationNotification;
    }

    /**
     * @param string $userId
     * @param int    $passwordCount
     */
    public function sendBadPasswordNotification(string $userId, int $passwordCount): void {
        $this->sendNotification(
            $this->badPasswordNotification,
            $userId,
            ['count' => $passwordCount]
        );
    }

    /**
     * @param string $receiverId
     * @param array  $owners
     */
    public function sendShareCreatedNotification(string $receiverId, array $owners): void {
        $this->sendNotification(
            $this->shareCreatedNotification,
            $receiverId,
            ['owners' => $owners]
        );
    }

    /**
     * @param string $userId
     * @param int    $passwords
     */
    public function sendShareLoopNotification(string $userId, int $passwords): void {
        $this->sendNotification(
            $this->shareLoopNotification,
            $userId,
            ['passwords' => $passwords]
        );
    }

    /**
     * @param string $userId
     * @param string $impersonatorId
     */
    public function sendImpersonationNotification(string $userId, string $impersonatorId): void {
        $this->sendNotification(
            $this->impersonationNotification,
            $userId,
            ['impersonator' => $impersonatorId]
        );
    }

    /**
     * @param AbstractNotification $notification
     * @param string               $userId
     * @param array                $parameters
     */
    protected function sendNotification(AbstractNotification $notification, string $userId, array $parameters): void {
        if($this->isNotificationEnabled($userId, $notification::TYPE)) {
            $notification->send($userId, $parameters);
        }
    }

    /**
     * @param INotification $notification
     * @param string        $languageCode The code of the language that should be used to prepare the notification
     *
     * @return INotification
     * @throws \InvalidArgumentException When the notification was not prepared by a notifier
     * @since 9.0.0
     */
    public function prepare(INotification $notification, $languageCode) {
        if($notification->getApp() !== Application::APP_NAME) {
            throw new \InvalidArgumentException();
        }

        $localisation = $this->l10NFactory->get(Application::APP_NAME, $languageCode);

        switch($notification->getSubject()) {
            case BadPasswordNotification::NAME:
                return $this->badPasswordNotification->process($notification, $localisation);
            case ShareCreatedNotification::NAME:
                return $this->shareCreatedNotification->process($notification, $localisation);
            case ShareLoopNotification::NAME:
                return $this->shareLoopNotification->process($notification, $localisation);
            case ImpersonationNotification::NAME:
                return $this->impersonationNotification->process($notification, $localisation);
        }

        return $notification;
    }

    /**
     * @param string $userId
     * @param string $type
     *
     * @return bool
     */
    protected function isNotificationEnabled(string $userId, string $type): bool {
        try {
            return $this->settings->get('user.notification.'.$type, $userId) === true;
        } catch(\Exception $e) {
            return false;
        }
    }
}