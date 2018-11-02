<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

/**
 * Class NotificationService
 *
 * @package OCA\Passwords\Notification
 */
class NotificationService implements INotifier {

    const NOTIFICATION_SHARE_LOOP   = 'share_loop';
    const NOTIFICATION_SHARE_CREATE = 'share_create';
    const NOTIFICATION_PASSWORD_BAD = 'bad_password';

    /**
     * @var SettingsService
     */
    protected $settings;

    /**
     * @var IFactory
     */
    protected $l10NFactory;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var IManager
     */
    protected $notificationManager;

    /**
     * NotificationService constructor.
     *
     * @param IFactory        $l10nFactory
     * @param IURLGenerator   $urlGenerator
     * @param UserService     $userService
     * @param SettingsService $settings
     * @param IManager        $notificationManager
     */
    public function __construct(
        IFactory $l10nFactory,
        UserService $userService,
        SettingsService $settings,
        IURLGenerator $urlGenerator,
        IManager $notificationManager
    ) {
        $this->settings            = $settings;
        $this->l10NFactory         = $l10nFactory;
        $this->userService         = $userService;
        $this->urlGenerator        = $urlGenerator;
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param string $userId
     * @param int    $passwords
     */
    public function sendBadPasswordNotification(string $userId, int $passwords): void {
        if(!$this->settings->get('user.notification.security', $userId)) return;

        $notification
            = $this->createNotification($userId)
                   ->setSubject(NotificationService::NOTIFICATION_PASSWORD_BAD, ['count' => $passwords])
                   ->setObject('object', 'password');
        $this->notificationManager->notify($notification);
    }

    /**
     * @param string $receiverId
     * @param array  $owners
     */
    public function sendShareCreateNotification(string $receiverId, array $owners): void {
        if(!$this->settings->get('user.notification.shares', $receiverId)) return;

        $notification
            = $this->createNotification($receiverId)
                   ->setSubject(NotificationService::NOTIFICATION_SHARE_CREATE, ['owners' => $owners])
                   ->setObject('share', 'create');
        $this->notificationManager->notify($notification);
    }

    /**
     * @param string $userId
     * @param int    $passwords
     */
    public function sendShareLoopNotification(string $userId, int $passwords): void {
        if(!$this->settings->get('user.notification.errors', $userId)) return;

        $notification
            = $this->createNotification($userId)
                   ->setSubject(NotificationService::NOTIFICATION_SHARE_LOOP, ['passwords' => $passwords])
                   ->setObject('share', 'loop');
        $this->notificationManager->notify($notification);
    }

    /**
     * @param string $userId
     *
     * @return INotification
     */
    protected function createNotification(string $userId): INotification {
        $icon = $this->urlGenerator->imagePath(Application::APP_NAME, 'app-dark.svg');

        return $this->notificationManager
            ->createNotification()
            ->setApp(Application::APP_NAME)
            ->setDateTime(new \DateTime())
            ->setUser($userId)
            ->setIcon($icon);
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
            case self::NOTIFICATION_PASSWORD_BAD:
                return $this->processBadPasswordNotification($notification, $localisation);
            case self::NOTIFICATION_SHARE_CREATE:
                return $this->processShareCreateNotification($notification, $localisation);
            case self::NOTIFICATION_SHARE_LOOP:
                return $this->processShareLoopNotification($notification, $localisation);
        }

        return $notification;
    }

    /**
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    protected function processBadPasswordNotification(INotification $notification, IL10N $localisation): INotification {
        $parameters = $notification->getSubjectParameters();
        $count = isset($parameters['count']) ? $parameters['count']:1;
        $link  = $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/security/2';

        $title = $localisation->n('One of your passwords is no longer secure', 'Some of your passwords are no longer secure', $count)
                 .'. '
                 .$localisation->n('Open the passwords app to change it.', 'Open the passwords app to change them.', $count);

        $message = [
            $localisation->t('Passwords regularly checks if your passwords have been compromised by a data breach.'),
            $localisation->n(
                'This security check has found that one of your passwords is insecure.',
                'This security check has found that %s of your passwords are insecure.',
                $count, [$count]
            )
        ];

        return $notification->setParsedSubject($title)
                            ->setParsedMessage(implode(' ', $message))
                            ->setLink($link);
    }

    /**
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    protected function processShareCreateNotification(INotification $notification, IL10N $localisation): INotification {
        $owners        = $notification->getSubjectParameters()['owners'];
        $ownerCount    = count($owners);
        $passwordCount = 0;

        if($ownerCount === 1) {
            $ownerId       = key($owners);
            $owner         = $this->userService->getUserName($ownerId);
            $passwordCount = $owners[ $ownerId ];

            $title = $localisation->n('%s shared a password with you.', '%s shared %s passwords with you.', $passwordCount, [$owner, $passwordCount]);
        } else {
            $params = [];
            foreach($owners as $ownerId => $amount) {
                if(count($params) < 4) $params[] = $this->userService->getUserName($ownerId);
                $passwordCount += $amount;
            }
            $params = array_reverse($params);
            array_unshift($params, $passwordCount, $ownerCount - 2);

            $text  = ($ownerCount > 2 ? '%5$s, %4$s':'%4$s').' and '.($ownerCount > 3 ? '%2$s others':'%3$s').' shared %1$s passwords with you.';
            $title = $localisation->t($text, $params);
        }

        $title .= ' '.$localisation->t('Open the passwords app to see '.($passwordCount === 1 ? 'it.':'them.'));
        $link  = $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/shared/0';

        return $notification->setParsedSubject($title)->setLink($link);
    }

    /**
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    protected function processShareLoopNotification(INotification $notification, IL10N $localisation): INotification {
        $count = $notification->getSubjectParameters()['passwords'];

        $title   = $localisation->t(($count === 1 ? 'One':'%s').' of your passwords could not be shared because the recipient already has access to '.($count === 1 ? 'it.':'them.'), [$count])
                   .' '.$localisation->t('Open the passwords app to see your shared passwords.');
        $message = $localisation->t('Sharing a password that has been shared with you can sometimes create a loop.')
                   .' '.$localisation->t('To prevent this, these passwords will not be shared.');
        $link    = $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/shared/1';

        return $notification->setParsedSubject($title)
                            ->setParsedMessage($message)
                            ->setLink($link);
    }
}