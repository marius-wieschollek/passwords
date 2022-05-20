<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Notification;

use Exception;
use OCA\Passwords\AppInfo\SystemRequirements;
use OCA\Passwords\Services\ConfigurationService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Notification\INotification;

/**
 * Class UpgradeRequiredNotification
 *
 * @package OCA\Passwords\Notification
 */
class UpgradeRequiredNotification extends AbstractNotification {

    const NAME                           = 'upgrade_required';
    const TYPE                           = 'admin';
    const MANUAL_URL_SYSTEM_REQUIREMENTS = 'https://git.mdns.eu/nextcloud/passwords/wikis/Administrators/Notifications/Platform-Support-Notification';

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @param IFactory             $l10nFactory
     * @param IURLGenerator        $urlGenerator
     * @param IManager             $notificationManager
     * @param ConfigurationService $config
     */
    public function __construct(IFactory $l10nFactory, IURLGenerator $urlGenerator, IManager $notificationManager, ConfigurationService $config) {
        parent::__construct($l10nFactory, $urlGenerator, $notificationManager);
        $this->config = $config;
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
                   ->setObject('app', 'requirements');

        $this->addRawLink($notification, self::MANUAL_URL_SYSTEM_REQUIREMENTS);

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
        $ncVersion = explode('.', $this->config->getSystemValue('version'), 2)[0];
        $parameters = $notification->getSubjectParameters();
        if(!isset($parameters['ncVersion']) || $parameters['ncVersion'] < intval($ncVersion)) {
            if(intval($ncVersion) >= SystemRequirements::NC_NOTIFICATION_ID) {
                return $notification
                    ->setParsedSubject($localisation->t('This notification can be deleted.'))
                    ->setParsedMessage('');
            }
        }


        $title   = $this->getTitle($localisation, $ncVersion);
        $message = $this->getMessage($localisation, $ncVersion, $parameters['appVersion']);
        $this->processLink($notification, self::MANUAL_URL_SYSTEM_REQUIREMENTS, $localisation->t('More information'));

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink(self::MANUAL_URL_SYSTEM_REQUIREMENTS);
    }

    /**
     * @param IL10N  $localisation
     * @param string $ncVersion
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation, string $ncVersion): string {
        return $localisation->t('Passwords ends updates for Nextcloud %s', [$ncVersion]);
    }

    /**
     * @param IL10N  $localisation
     * @param string $ncVersion
     * @param string $appVersion
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, string $ncVersion, string $appVersion): string {
        return
            $localisation->t(
                'Passwords %s is the last update for Nextcloud %s.',
                [
                    $appVersion,
                    $ncVersion,
                ]
            ).
            ' '.
            $localisation->t(
                'Upgrade to Nextcloud %s and PHP %s (or PHP %s for LSR) for future upgrades.',
                [
                    SystemRequirements::NC_UPGRADE_MINIMUM,
                    SystemRequirements::PHP_UPGRADE_MINIMUM,
                    SystemRequirements::PHP_UPGRADE_MINIMUM_LSR,
                ]
            );
    }
}