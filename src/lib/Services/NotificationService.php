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
use OCA\Passwords\Notification\BesticonApiNotification;
use OCA\Passwords\Notification\EmptyRequiredSettingNotification;
use OCA\Passwords\Notification\ImpersonationNotification;
use OCA\Passwords\Notification\LegacyApiNotification;
use OCA\Passwords\Notification\LoginAttemptNotification;
use OCA\Passwords\Notification\NewClientNotification;
use OCA\Passwords\Notification\ShareCreatedNotification;
use OCA\Passwords\Notification\ShareLoopNotification;
use OCA\Passwords\Notification\SurveyNotification;
use OCA\Passwords\Notification\UpgradeRequiredNotification;
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
     * @var UserSettingsService
     */
    protected $settings;

    /**
     * @var IFactory
     */
    protected $l10NFactory;

    /**
     * @var SurveyNotification
     */
    protected $surveyNotification;

    /**
     * @var LegacyApiNotification
     */
    protected $legacyApiNotification;

    /**
     * @var NewClientNotification
     */
    protected $newClientNotification;

    /**
     * @var ShareLoopNotification
     */
    protected $shareLoopNotification;

    /**
     * @var BadPasswordNotification
     */
    protected $badPasswordNotification;

    /**
     * @var BesticonApiNotification
     */
    protected $besticonApiNotification;

    /**
     * @var ShareCreatedNotification
     */
    protected $shareCreatedNotification;

    /**
     * @var LoginAttemptNotification
     */
    protected $loginAttemptNotification;

    /**
     * @var ImpersonationNotification
     */
    protected $impersonationNotification;

    /**
     * @var UpgradeRequiredNotification
     */
    protected $upgradeRequiredNotification;

    /**
     * @var EmptyRequiredSettingNotification
     */
    protected $emptyRequiredSettingNotification;

    /**
     * NotificationService constructor.
     *
     * @param IFactory                         $l10nFactory
     * @param UserSettingsService              $settings
     * @param SurveyNotification               $surveyNotification
     * @param NewClientNotification            $newClientNotification
     * @param ShareLoopNotification            $shareLoopNotification
     * @param LegacyApiNotification            $legacyApiNotification
     * @param BadPasswordNotification          $badPasswordNotification
     * @param BesticonApiNotification          $besticonApiNotification
     * @param ShareCreatedNotification         $shareCreatedNotification
     * @param LoginAttemptNotification         $loginAttemptNotification
     * @param ImpersonationNotification        $impersonationNotification
     * @param UpgradeRequiredNotification      $upgradeRequiredNotification
     * @param EmptyRequiredSettingNotification $emptyRequiredSettingNotification
     */
    public function __construct(
        IFactory $l10nFactory,
        UserSettingsService $settings,
        SurveyNotification $surveyNotification,
        NewClientNotification $newClientNotification,
        ShareLoopNotification $shareLoopNotification,
        LegacyApiNotification $legacyApiNotification,
        BadPasswordNotification $badPasswordNotification,
        BesticonApiNotification $besticonApiNotification,
        ShareCreatedNotification $shareCreatedNotification,
        LoginAttemptNotification $loginAttemptNotification,
        ImpersonationNotification $impersonationNotification,
        UpgradeRequiredNotification $upgradeRequiredNotification,
        EmptyRequiredSettingNotification $emptyRequiredSettingNotification
    ) {
        $this->settings                         = $settings;
        $this->l10NFactory                      = $l10nFactory;
        $this->surveyNotification               = $surveyNotification;
        $this->newClientNotification            = $newClientNotification;
        $this->legacyApiNotification            = $legacyApiNotification;
        $this->shareLoopNotification            = $shareLoopNotification;
        $this->badPasswordNotification          = $badPasswordNotification;
        $this->besticonApiNotification          = $besticonApiNotification;
        $this->shareCreatedNotification         = $shareCreatedNotification;
        $this->loginAttemptNotification         = $loginAttemptNotification;
        $this->impersonationNotification        = $impersonationNotification;
        $this->upgradeRequiredNotification      = $upgradeRequiredNotification;
        $this->emptyRequiredSettingNotification = $emptyRequiredSettingNotification;
    }

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
     * @param string $userId
     * @param string $client
     * @param bool   $revoked
     */
    public function sendLoginAttemptNotification(string $userId, string $client, $revoked = false): void {
        $this->sendNotification(
            $this->loginAttemptNotification,
            $userId,
            ['client' => $client, 'revoked' => $revoked]
        );
    }

    /**
     * @param string $userId
     * @param string $client
     */
    public function sendLegacyApiNotification(string $userId, string $client): void {
        $this->sendNotification(
            $this->legacyApiNotification,
            $userId,
            ['client' => $client]
        );
    }

    /**
     * @param string $userId
     */
    public function sendSurveyNotification(string $userId): void {
        $this->sendNotification(
            $this->surveyNotification,
            $userId,
            []
        );
    }

    /**
     * @param string $userId
     * @param string $setting
     */
    public function sendEmptyRequiredSettingNotification(string $userId, string $setting): void {
        $this->sendNotification(
            $this->emptyRequiredSettingNotification,
            $userId,
            ['setting' => $setting]
        );
    }

    /**
     * @param string $userId
     */
    public function sendBesticonApiNotification(string $userId): void {
        $this->sendNotification(
            $this->besticonApiNotification,
            $userId,
            []
        );
    }

    /**
     * @param string $userId
     * @param string $release
     * @param string $ncVersion
     * @param string $phpVersion
     */
    public function sendUpgradeRequiredNotification(string $userId, string $release, string $ncVersion, string $phpVersion): void {
        $this->sendNotification(
            $this->upgradeRequiredNotification,
            $userId,
            [$release, $ncVersion, $phpVersion]
        );
    }

    /**
     * @param string $userId
     * @param string $client
     */
    public function sendNewClientNotification(string $userId, string $client): void {
        $this->sendNotification(
            $this->newClientNotification,
            $userId,
            ['client' => $client]
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
     * @param string        $languageCode
     *
     * @return INotification
     * @throws \Exception
     */
    public function prepare(INotification $notification, string $languageCode): INotification {
        if($notification->getApp() !== Application::APP_NAME) {
            throw new \InvalidArgumentException();
        }

        $localisation = $this->l10NFactory->get(Application::APP_NAME, $languageCode);
        switch($notification->getSubject()) {
            case EmptyRequiredSettingNotification::NAME:
                return $this->emptyRequiredSettingNotification->process($notification, $localisation);
            case UpgradeRequiredNotification::NAME:
                return $this->upgradeRequiredNotification->process($notification, $localisation);
            case BadPasswordNotification::NAME:
                return $this->badPasswordNotification->process($notification, $localisation);
            case ShareCreatedNotification::NAME:
                return $this->shareCreatedNotification->process($notification, $localisation);
            case ImpersonationNotification::NAME:
                return $this->impersonationNotification->process($notification, $localisation);
            case BesticonApiNotification::NAME:
                return $this->besticonApiNotification->process($notification, $localisation);
            case LoginAttemptNotification::NAME:
                return $this->loginAttemptNotification->process($notification, $localisation);
            case NewClientNotification::NAME:
                return $this->newClientNotification->process($notification, $localisation);
            case ShareLoopNotification::NAME:
                return $this->shareLoopNotification->process($notification, $localisation);
            case LegacyApiNotification::NAME:
                return $this->legacyApiNotification->process($notification, $localisation);
            case SurveyNotification::NAME:
                return $this->surveyNotification->process($notification, $localisation);
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