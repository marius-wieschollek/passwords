<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use DateTime;
use DateTimeZone;
use OCA\Passwords\Helper\Icon\FallbackIconGenerator;
use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\NotificationService;

/**
 * Class BetterIdeaHelper
 */
class BestIconHelper extends AbstractFaviconHelper {

    const BESTICON_INSTANCE_1     = 'https://ncpw-besticon-01.herokuapp.com/icon';
    const BESTICON_INSTANCE_2     = 'https://ncpw-besticon-02.herokuapp.com/icon';
    const BESTICON_CONFIG_KEY     = 'service/favicon/bi/url';
    const BESTICON_COUNTER_KEY    = 'service/favicon/bi/counter';
    const BESTICON_INSTANCE_LIMIT = 75;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var AdminUserHelper
     */
    protected $adminHelper;

    /**
     * @var NotificationService
     */
    protected $notifications;

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_BESTICON;

    /**
     * BestIconHelper constructor.
     *
     * @param ConfigurationService  $config
     * @param HelperService         $helperService
     * @param AdminUserHelper       $adminHelper
     * @param FileCacheService      $fileCacheService
     * @param NotificationService   $notificationService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        ConfigurationService $config,
        HelperService $helperService,
        AdminUserHelper $adminHelper,
        FileCacheService $fileCacheService,
        NotificationService $notificationService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->config        = $config;
        $this->adminHelper   = $adminHelper;
        $this->notifications = $notificationService;

        parent::__construct($helperService, $fileCacheService, $fallbackIconGenerator);
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {
        $fallbackColor = substr($this->fallbackIconGenerator->stringToColor($domain), 1);
        $serviceUrl    = $this->config->getAppValue(self::BESTICON_CONFIG_KEY, '');
        if(empty($serviceUrl)) $serviceUrl = $this->getSharedInstanceUrl();

        return "{$serviceUrl}?size=16..128..256&fallback_icon_color={$fallbackColor}&url={$domain}&formats=png,ico,gif,jpg";
    }

    /**
     * @return string
     */
    protected function getSharedInstanceUrl(): string {
        try {
            $time = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        } catch(\Exception $e) {
            return self::BESTICON_INSTANCE_1;
        }

        $this->config->clearCache();
        [$week, $count] = explode(':', $this->config->getAppValue(self::BESTICON_COUNTER_KEY, '0:0'));
        if($week !== $time->format('W')) $count = 0;
        $count++;
        $this->config->setAppValue(self::BESTICON_COUNTER_KEY, "{$time->format('W')}:{$count}");

        if($count === self::BESTICON_INSTANCE_LIMIT) {
            foreach($this->adminHelper->getAdmins() as $admin) {
                $this->notifications->sendBesticonApiNotification($admin->getUID());
            }
        }

        return $time->format('H') < 12 ? self::BESTICON_INSTANCE_1:self::BESTICON_INSTANCE_2;
    }
}