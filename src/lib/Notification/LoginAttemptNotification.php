<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use Exception;
use InvalidArgumentException;
use OCA\Passwords\Services\EnvironmentService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class LoginAttemptNotification
 *
 * @package OCA\Passwords\Notification
 */
class LoginAttemptNotification extends AbstractNotification {

    const NAME = 'user_login_attempts';
    const TYPE = 'security';

    /**
     * @var EnvironmentService
     */
    protected EnvironmentService $environment;

    /**
     * LoginAttemptNotification constructor.
     *
     * @param IFactory           $l10nFactory
     * @param IURLGenerator      $urlGenerator
     * @param IManager           $notificationManager
     * @param EnvironmentService $environment
     */
    public function __construct(
        IFactory $l10nFactory,
        IURLGenerator $urlGenerator,
        IManager $notificationManager,
        EnvironmentService $environment
    ) {
        $this->environment = $environment;

        parent::__construct($l10nFactory, $urlGenerator, $notificationManager);
    }

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     *
     * @throws Exception
     */
    public function send(string $userId, array $parameters = []): void {
        $notification
            = $this->createNotification($userId)
                   ->setSubject(self::NAME, $parameters)
                   ->setObject('object', 'login');
        $this->addRawLink($notification, $this->getLink());

        $this->notificationManager->notify($notification);
    }

    /**
     * Process the notification for display
     *
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     * @throws InvalidArgumentException
     */
    public function process(INotification $notification, IL10N $localisation): INotification {
        if($this->environment->isImpersonating()) throw new InvalidArgumentException();
        $link    = $this->getLink();
        $title   = $localisation->t('Suspicious amount of failed login attempts detected.');
        $message = $this->getMessage($localisation, $notification->getSubjectParameters());
        $this->processLink($notification, $link, $localisation->t('Open passwords'));

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink($link);
    }

    /**
     * @param IL10N $localisation
     * @param array $parameters
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, array $parameters): string {
        $message = $localisation->t('We have detected several failed attempts to unlock your password database by "%s".', [$parameters['client']])
                   .' '.
                   $localisation->t('This could indicate that someone is trying to break into your account.')
                   .' ';

        if($parameters['revoked'] === true) {
            $message .= $localisation->t('To prevent further attempts, the API credentials of this client were revoked.')
                        .' '.
                        $localisation->t('Also, password based API authentication has been disabled.')
                        .' '.
                        $localisation->t('If you want to continue using this client, you need to create a new token for it.')
                        .' '.
                        $localisation->t('If you don\'t know this client, please change your password and review your device list.');
        } else {
            $message .= $localisation->t('To prevent further attempts, password based API authentication has been disabled.')
                        .' '.
                        $localisation->t('To enable it again, log in with the webapp or any client using token authentication.')
                        .' '.
                        $localisation->t('To increase security, we recommend using device specific tokens instead of your password.');
        }

        return $message;
    }

    /**
     * @return string
     */
    protected function getLink(): string {
        return $this->urlGenerator->linkToRouteAbsolute('passwords.page.index');
    }
}