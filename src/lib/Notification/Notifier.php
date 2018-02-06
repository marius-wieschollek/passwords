<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 15.01.18
 * Time: 21:09
 */

namespace OCA\Passwords\Notification;

use OCA\Passwords\AppInfo\Application;
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
     * @var \OCP\IL10N
     */
    protected $localisation;

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
        $this->localisation = $this->l10NFactory->get(Application::APP_NAME, $languageCode);

        if($notification->getSubject() === self::NOTIFICATION_SHARE_LOOP) {
            return $this->processShareLoopNotification($notification);
        } else if($notification->getSubject() === self::NOTIFICATION_PASSWORD_BAD) {
            return $this->processBadPasswordNotification($notification);
        }

        return $notification;
    }

    /**
     * @param INotification $notification
     *
     * @return INotification
     */
    protected function processShareLoopNotification(INotification $notification): INotification {
        $user = $this->userManager->get($notification->getObjectId());

        $title   = $this->localisation->t('Password not shared');
        $message = $this->localisation->t('A password was not shared with %1$s because it already is', [$user->getDisplayName()]);
        $link    = $this->urlGenerator->linkToRoute('passwords.page.index');
        $icon    = $this->urlGenerator->imagePath(Application::APP_NAME, 'app-dark.svg');

        return $notification->setParsedSubject($title)
                            ->setParsedMessage($message)
                            ->setLink($link)
                            ->setIcon($icon);
    }

    /**
     * @param INotification $notification
     *
     * @return INotification
     */
    protected function processBadPasswordNotification(INotification $notification): INotification {
        $title   = $this->localisation->t('Insecure password found');
        $message = $this->localisation->t('One of your passwords is no longer secure');
        $link    = $this->urlGenerator->linkToRoute('passwords.page.index').'#/security/2';
        $icon    = $this->urlGenerator->imagePath(Application::APP_NAME, 'app-dark.svg');

        return $notification->setParsedSubject($title)
                            ->setParsedMessage($message)
                            ->setLink($link)
                            ->setIcon($icon);
    }
}