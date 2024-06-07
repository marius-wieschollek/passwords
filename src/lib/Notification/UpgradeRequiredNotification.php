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
    const MANUAL_URL_SYSTEM_REQUIREMENTS = 'https://git.mdns.eu/nextcloud/passwords/-/wikis/Administrators/Notifications/Platform-Support-Notification';

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
        $ncVersion     = \OC_Util::getVersion()[0];
        $phpVersion    = PHP_VERSION_ID;
        $parameters    = $notification->getSubjectParameters();
        $isNcOutdated  = $ncVersion < SystemRequirements::NC_NOTIFICATION_ID;
        $isPhpOutdated = $phpVersion < SystemRequirements::PHP_NOTIFICATION_ID;

        if(!$isNcOutdated && !$isPhpOutdated || !isset($parameters['appVersion'])) {
            return $notification
                ->setParsedSubject($localisation->t('This notification can be deleted.'))
                ->setParsedMessage('');
        }

        $phpVersionString = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        $title   = $this->getTitle($localisation, $ncVersion, $phpVersionString, $isNcOutdated, $isPhpOutdated);
        $message = $this->getMessage($localisation, $ncVersion, $phpVersionString, $parameters['appVersion'], $isNcOutdated, $isPhpOutdated);
        $this->processLink($notification, self::MANUAL_URL_SYSTEM_REQUIREMENTS, $localisation->t('More information'));

        return $notification
            ->setParsedSubject($title)
            ->setParsedMessage($message)
            ->setLink(self::MANUAL_URL_SYSTEM_REQUIREMENTS);
    }

    /**
     * @param IL10N  $localisation
     * @param string $ncVersion
     * @param string $phpVersionString
     * @param bool   $isNcOutdated
     * @param bool   $isPhpOutdated
     *
     * @return string
     */
    protected function getTitle(IL10N $localisation, string $ncVersion, string $phpVersionString, bool $isNcOutdated, bool $isPhpOutdated): string {
        if($isNcOutdated && $isPhpOutdated) {
            return $localisation->t('Passwords ends updates for Nextcloud %1$s and PHP %2$s', [$ncVersion, $phpVersionString]);
        }
        if($isPhpOutdated) {
            return $localisation->t('Passwords ends updates for PHP %s', [$phpVersionString]);
        }

        return $localisation->t('Passwords ends updates for Nextcloud %s', [$ncVersion]);
    }

    /**
     * @param IL10N  $localisation
     * @param string $ncVersion
     * @param string $phpVersionString
     * @param string $appVersion
     * @param bool   $isNcOutdated
     * @param bool   $isPhpOutdated
     *
     * @return string
     */
    protected function getMessage(IL10N $localisation, string $ncVersion, string $phpVersionString, string $appVersion, bool $isNcOutdated, bool $isPhpOutdated): string {
        $text1 = 'Passwords %1$s is the last update for Nextcloud %2$s.';
        $text2 = 'Upgrade to Nextcloud %1$s for future upgrades.';

        if($isNcOutdated && $isPhpOutdated) {
            $text1 = 'Passwords %1$s is the last update for Nextcloud %2$s and PHP %3$s.';
            $text2 = 'Upgrade to Nextcloud %1$s and PHP %2$s (or PHP %3$s for LSR) for future upgrades.';
        } else if($isPhpOutdated) {
            $text1 = 'Passwords %1$s is the last update for PHP %3$s.';
            $text2 = 'Upgrade to PHP %2$s (or PHP %3$s for LSR) for future upgrades.';
        }

        return
            $localisation->t(
                $text1,
                [$appVersion, $ncVersion, $phpVersionString]
            ).
            ' '.
            $localisation->t(
                $text2,
                [SystemRequirements::NC_UPGRADE_MINIMUM, SystemRequirements::PHP_UPGRADE_MINIMUM, SystemRequirements::PHP_UPGRADE_MINIMUM_LSR,]
            );
    }
}