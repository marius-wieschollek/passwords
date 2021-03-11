<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use DateTime;
use Exception;
use IntlDateFormatter;
use InvalidArgumentException;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\UserChallengeService;
use OCA\Passwords\Services\UserService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class ImpersonationNotification
 *
 * @package OCA\Passwords\Notification
 */
class ImpersonationNotification extends AbstractNotification {

    const NAME = 'user_impersonation';
    const TYPE = 'security';

    /**
     * @var EnvironmentService
     */
    protected EnvironmentService $environment;

    /**
     * @var UserService
     */
    protected UserService $userService;

    /**
     * @var UserChallengeService
     */
    protected UserChallengeService $challengeService;

    /**
     * ImpersonationNotification constructor.
     *
     * @param IFactory             $l10nFactory
     * @param UserService          $userService
     * @param IURLGenerator        $urlGenerator
     * @param IManager             $notificationManager
     * @param EnvironmentService   $environment
     * @param UserChallengeService $challengeService
     */
    public function __construct(
        IFactory $l10nFactory,
        UserService $userService,
        IURLGenerator $urlGenerator,
        IManager $notificationManager,
        EnvironmentService $environment,
        UserChallengeService $challengeService
    ) {
        $this->environment      = $environment;
        $this->userService      = $userService;
        $this->challengeService = $challengeService;

        parent::__construct($l10nFactory, $urlGenerator, $notificationManager);
    }

    /**
     * @param string $userId
     * @param array  $parameters
     *
     * @throws Exception
     */
    public function send(string $userId, array $parameters = []): void {
        $parameters['time'] = time();

        $notification
            = $this->createNotification($userId)
                   ->setSubject(self::NAME, $parameters)
                   ->setObject('object', 'login');
        $this->addRawLink($notification, $this->getLink());

        $this->notificationManager->notify($notification);
    }

    /**
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     * @throws InvalidArgumentException
     */
    public function process(INotification $notification, IL10N $localisation): INotification {
        if($this->environment->isImpersonating()) throw new InvalidArgumentException();

        $link    = $this->getLink();
        $title   = $localisation->t('Administrative access to your account');
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
        $dateTime = new DateTime();
        $dateTime->setTimestamp($parameters['time']);

        $formatter = new IntlDateFormatter(
            $localisation->getLocaleCode(),
            IntlDateFormatter::LONG,
            IntlDateFormatter::SHORT
        );

        $date         = $formatter->format($dateTime);
        $impersonator = $this->userService->getUserName($parameters['impersonator']);

        if($this->challengeService->hasChallenge()) {
            return $localisation->t('%s tried to log into your account on %s.', [$impersonator, $date])
                   .' '.
                   $localisation->t('Since you use a master password, this does not mean that access to your data was granted.');
        }

        return $localisation->t('%s logged into your account on %s.', [$impersonator, $date])
               .' '.
               $localisation->t('To prevent unwanted access to your data, you should set up a master password.');
    }

    /**
     * @return string
     */
    protected function getLink(): string {
        return $this->urlGenerator->linkToRouteAbsolute('passwords.page.index');
    }
}