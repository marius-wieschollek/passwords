<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\DeferredActivationService;
use OCA\Passwords\Services\UserChallengeService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class LegacyApiNotification
 *
 * @package OCA\Passwords\Notification
 */
class LegacyApiNotification extends AbstractNotification {

    const NAME = 'legacy_api';
    const TYPE = 'errors';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var UserChallengeService
     */
    protected $challengeService;

    /**
     * @var DeferredActivationService
     */
    protected $deferredActivation;

    /**
     * LegacyApiNotification constructor.
     *
     * @param IFactory                  $l10nFactory
     * @param IURLGenerator             $urlGenerator
     * @param ConfigurationService      $config
     * @param IManager                  $notificationManager
     * @param UserChallengeService      $challengeService
     * @param DeferredActivationService $deferredActivation
     */
    public function __construct(
        IFactory $l10nFactory,
        IURLGenerator $urlGenerator,
        ConfigurationService $config,
        IManager $notificationManager,
        UserChallengeService $challengeService,
        DeferredActivationService $deferredActivation
    ) {
        $this->config             = $config;
        $this->challengeService   = $challengeService;
        $this->deferredActivation = $deferredActivation;

        parent::__construct($l10nFactory, $urlGenerator, $notificationManager);
    }

    /**
     * Send the notification
     *
     * @param string $userId
     * @param array  $parameters
     */
    public function send(string $userId, array $parameters = []): void {
        if(!$this->deferredActivation->check('legacy-client-warning', true)) return;
        if($this->checkIfAlreadyNotified($userId, $parameters['client'])) return;

        $notification = $this->createNotification($userId)
                             ->setSubject(self::NAME, $parameters)
                             ->setObject('warning', 'legacy_api');

        $this->notificationManager->notify($notification);
    }

    /**
     * Process the notification for display
     *
     * @param INotification $notification
     * @param IL10N         $localisation
     *
     * @return INotification
     */
    public function process(INotification $notification, IL10N $localisation): INotification {
        $client = $notification->getSubjectParameters()['client'];

        $title   = $localisation->t('Legacy API: Time to say goodbye!');
        $message = $this->getMessage($localisation, $client);
        $link    = $this->urlGenerator->linkToRouteAbsolute('passwords.page.index').'#/apps';

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink($link);
    }

    /**
     * @param IL10N $localisation
     * @param       $client
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, string $client): string {
        $message = $localisation->t('"%s" uses an outdated API which will be removed.', [$client]);

        if($this->challengeService->hasChallenge()) {
            $message .= ' '.$localisation->t('This API does not support encryption and will deliver incomplete data.');
        }

        return $message
               .' '.
               $localisation->t('Please check if an update is available or visit our app section to find a replacement.');
    }

    /**
     * @param string $userId
     * @param string $client
     *
     * @return bool
     */
    protected function checkIfAlreadyNotified(string $userId, string $client): bool {
        try {
            $configKey    = 'legacy/notify/'.md5($client);
            $lastNotified = $this->config->getUserValue($configKey, 0, $userId);
            if($lastNotified > strtotime('-1 week')) return true;
            $this->config->setUserValue($configKey, time(), $userId);
        } catch(\Exception $e) {
        }

        return false;
    }
}