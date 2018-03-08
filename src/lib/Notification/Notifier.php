<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use OCA\Passwords\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

/**
 * Class Notifier
 *
 * @package OCA\Passwords\Notification
 */
class Notifier implements INotifier {

    const NOTIFICATION_SHARE_LOOP   = 'share_loop';
    const NOTIFICATION_PASSWORD_BAD = 'bad_password';

    /**
     * @var IFactory
     */
    protected $l10NFactory;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * Notifier constructor.
     *
     * @param IFactory      $l10nFactory
     * @param IURLGenerator $urlGenerator
     * @param IUserManager  $userManager
     */
    public function __construct(IFactory $l10nFactory, IURLGenerator $urlGenerator, IUserManager $userManager) {
        $this->l10NFactory  = $l10nFactory;
        $this->urlGenerator = $urlGenerator;
        $this->userManager  = $userManager;
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
        if($notification->getSubject() === self::NOTIFICATION_SHARE_LOOP) {
            return $this->processShareLoopNotification($notification, $localisation);
        } else if($notification->getSubject() === self::NOTIFICATION_PASSWORD_BAD) {
            return $this->processBadPasswordNotification($notification, $localisation);
        }

        return $notification;
    }

    /**
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    protected function processShareLoopNotification(INotification $notification, IL10N $localisation): INotification {
        $user = $this->userManager->get($notification->getObjectId());

        $title   = $localisation->t('Password not shared');
        $message = $localisation->t('A password was not shared with %1$s because it already is', [$user->getDisplayName()]);
        $link    = $this->urlGenerator->linkToRoute('passwords.page.index');
        $icon    = $this->urlGenerator->imagePath(Application::APP_NAME, 'app-dark.svg');

        return $notification->setParsedSubject($title)
                            ->setParsedMessage($message)
                            ->setLink($link)
                            ->setIcon($icon);
    }

    /**
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    protected function processBadPasswordNotification(INotification $notification, IL10N $localisation): INotification {
        $count = $notification->getSubjectParameters()['count'];
        $link  = $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/security/2';
        $icon  = $this->urlGenerator->imagePath(Application::APP_NAME, 'app-dark.svg');

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
                            ->setLink($link)
                            ->setIcon($icon);
    }
}