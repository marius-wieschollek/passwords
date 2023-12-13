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

namespace OCA\Passwords\Services;

use Exception;
use InvalidArgumentException;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Notification\AbstractNotification;
use OCA\Passwords\Notification\BackupFailedNotification;
use OCA\Passwords\Notification\BackupRestoredNotification;
use OCA\Passwords\Notification\BadPasswordNotification;
use OCA\Passwords\Notification\BesticonApiNotification;
use OCA\Passwords\Notification\BreachedPasswordsUpdateFailedNotification;
use OCA\Passwords\Notification\EmptyRequiredSettingNotification;
use OCA\Passwords\Notification\ExportNotPossibleNotification;
use OCA\Passwords\Notification\ImpersonationNotification;
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
     * NotificationService constructor.
     *
     * @param IFactory                         $l10NFactory
     * @param UserSettingsService              $settings
     * @param SurveyNotification               $surveyNotification
     * @param ConfigurationService             $configurationService
     * @param NewClientNotification            $newClientNotification
     * @param ShareLoopNotification            $shareLoopNotification
     * @param BadPasswordNotification          $badPasswordNotification
     * @param BesticonApiNotification          $besticonApiNotification
     * @param ShareCreatedNotification         $shareCreatedNotification
     * @param LoginAttemptNotification         $loginAttemptNotification
     * @param BackupFailedNotification         $backupFailedNotification
     * @param ImpersonationNotification        $impersonationNotification
     * @param BackupRestoredNotification       $backupRestoredNotification
     * @param UpgradeRequiredNotification      $upgradeRequiredNotification
     * @param EmptyRequiredSettingNotification $emptyRequiredSettingNotification
     * @param ExportNotPossibleNotification    $exportNotPossibleNotification
     */
    public function __construct(
        protected IFactory                                  $l10NFactory,
        protected UserSettingsService                       $settings,
        protected SurveyNotification                        $surveyNotification,
        protected ConfigurationService                      $configurationService,
        protected NewClientNotification                     $newClientNotification,
        protected ShareLoopNotification                     $shareLoopNotification,
        protected BadPasswordNotification                   $badPasswordNotification,
        protected BesticonApiNotification                   $besticonApiNotification,
        protected ShareCreatedNotification                  $shareCreatedNotification,
        protected LoginAttemptNotification                  $loginAttemptNotification,
        protected BackupFailedNotification                  $backupFailedNotification,
        protected ImpersonationNotification                 $impersonationNotification,
        protected BackupRestoredNotification                $backupRestoredNotification,
        protected UpgradeRequiredNotification               $upgradeRequiredNotification,
        protected EmptyRequiredSettingNotification          $emptyRequiredSettingNotification,
        protected ExportNotPossibleNotification             $exportNotPossibleNotification,
        protected BreachedPasswordsUpdateFailedNotification $breachedPasswordsUpdateFailedNotification
    ) {
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
     * @param int    $ncVersion
     * @param int    $phpVersion
     * @param string $appVersion
     *
     * @throws Exception
     */
    public function sendUpgradeRequiredNotification(string $userId, int $ncVersion, int $phpVersion, string $appVersion): void {
        $date = date('Y-m');
        if($this->configurationService->getUserValue('notification/eol', '0-0', $userId) !== $date) {
            $this->sendNotification(
                $this->upgradeRequiredNotification,
                $userId,
                ['ncVersion' => $ncVersion, 'phpVersion' => $phpVersion, 'appVersion' => $appVersion]
            );
            $this->configurationService->setUserValue('notification/eol', $date, $userId);
        }
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
     * @param string $userId
     * @param string $backup
     */
    public function sendBackupRestoredNotification(string $userId, string $backup): void {
        $this->sendNotification(
            $this->backupRestoredNotification,
            $userId,
            ['backup' => $backup]
        );
    }

    /**
     * @param string $userId
     * @param string $backup
     */
    public function sendBackupFailedNotification(string $userId, string $backup): void {
        $this->sendNotification(
            $this->backupFailedNotification,
            $userId,
            ['backup' => $backup]
        );
    }

    public function sendUserExportNotPossibleNotification(string $userId, string $reason) {
        $this->sendNotification(
            $this->exportNotPossibleNotification,
            $userId,
            ['reason' => $reason]
        );
    }

    public function sendBreachedPasswordsUpdateFailedNotification(string $userId, string $reason) {
        $this->sendNotification(
            $this->breachedPasswordsUpdateFailedNotification,
            $userId,
            ['reason' => $reason]
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
     * @throws Exception
     */
    public function prepare(INotification $notification, string $languageCode): INotification {
        if($notification->getApp() !== Application::APP_NAME) throw new InvalidArgumentException();

        $localisation = $this->l10NFactory->get(Application::APP_NAME, $languageCode);

        return match ($notification->getSubject()) {
            BreachedPasswordsUpdateFailedNotification::NAME => $this->breachedPasswordsUpdateFailedNotification->process($notification, $localisation),
            EmptyRequiredSettingNotification::NAME => $this->emptyRequiredSettingNotification->process($notification, $localisation),
            ExportNotPossibleNotification::NAME => $this->exportNotPossibleNotification->process($notification, $localisation),
            UpgradeRequiredNotification::NAME => $this->upgradeRequiredNotification->process($notification, $localisation),
            BackupRestoredNotification::NAME => $this->backupRestoredNotification->process($notification, $localisation),
            BackupFailedNotification::NAME => $this->backupFailedNotification->process($notification, $localisation),
            BadPasswordNotification::NAME => $this->badPasswordNotification->process($notification, $localisation),
            ShareCreatedNotification::NAME => $this->shareCreatedNotification->process($notification, $localisation),
            ImpersonationNotification::NAME => $this->impersonationNotification->process($notification, $localisation),
            BesticonApiNotification::NAME => $this->besticonApiNotification->process($notification, $localisation),
            LoginAttemptNotification::NAME => $this->loginAttemptNotification->process($notification, $localisation),
            NewClientNotification::NAME => $this->newClientNotification->process($notification, $localisation),
            ShareLoopNotification::NAME => $this->shareLoopNotification->process($notification, $localisation),
            SurveyNotification::NAME => $this->surveyNotification->process($notification, $localisation),
            default => $notification,
        };
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
        } catch(Exception $e) {
            return false;
        }
    }
}