<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use Exception;
use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Icon\FallbackIconGenerator;
use OCA\Passwords\Helper\Time\DateTimeHelper;
use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\NotificationService;
use OCP\AppFramework\QueryException;

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
     * @var DateTimeHelper
     */
    protected $dateTime;

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
     * @param DateTimeHelper        $dateTime
     * @param ConfigurationService  $config
     * @param HelperService         $helperService
     * @param AdminUserHelper       $adminHelper
     * @param FileCacheService      $fileCacheService
     * @param NotificationService   $notificationService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws QueryException
     */
    public function __construct(
        DateTimeHelper $dateTime,
        RequestHelper $httpRequest,
        ConfigurationService $config,
        HelperService $helperService,
        AdminUserHelper $adminHelper,
        FileCacheService $fileCacheService,
        NotificationService $notificationService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->config        = $config;
        $this->dateTime      = $dateTime;
        $this->adminHelper   = $adminHelper;
        $this->notifications = $notificationService;

        parent::__construct($httpRequest, $helperService, $fileCacheService, $fallbackIconGenerator);
    }

    /**
     * @param string $domain
     * @param string $protocol
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain, string $protocol = 'https'): string {
        $fallbackColor = substr($this->fallbackIconGenerator->stringToColor($domain), 1);
        $serviceUrl    = $this->config->getAppValue(self::BESTICON_CONFIG_KEY, '');
        if(empty($serviceUrl)) $serviceUrl = $this->getSharedInstanceUrl();

        return "{$serviceUrl}?size=16..128..256&fallback_icon_color={$fallbackColor}&url={$protocol}://{$domain}&formats=png,ico,gif,jpg";
    }

    /**
     * @param string $domain
     *
     * @return null|string
     */
    protected function getFaviconData(string $domain): ?string {
        $url  = $this->getFaviconUrl($domain);
        $data = $this->getHttpRequest($url);

        if($data !== null) return $data;

        $url = $this->getFaviconUrl($domain, 'http');

        return $this->getHttpRequest($url);
    }

    /**
     * @return string
     */
    protected function getSharedInstanceUrl(): string {
        try {
            $currentWeek = $this->dateTime->getInternationalWeek();
            $currentHour = $this->dateTime->getInternationalHour();
        } catch(Exception $e) {
            return self::BESTICON_INSTANCE_1;
        }

        $this->config->clearCache();
        [$week, $count, $notified] = explode(':', $this->config->getAppValue(self::BESTICON_COUNTER_KEY, '0:0:0'));
        if(intval($week) !== $currentWeek) $count = 0;
        $count++;
        if($count >= self::BESTICON_INSTANCE_LIMIT && $notified === '0') {
            $notified = '1';
            foreach($this->adminHelper->getAdmins() as $admin) {
                $this->notifications->sendBesticonApiNotification($admin->getUID());
            }
        }
        $this->config->setAppValue(self::BESTICON_COUNTER_KEY, "{$currentWeek}:{$count}:{$notified}");

        return $currentHour < 12 ? self::BESTICON_INSTANCE_1:self::BESTICON_INSTANCE_2;
    }
}