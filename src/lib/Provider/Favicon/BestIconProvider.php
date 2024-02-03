<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Provider\Favicon;

use OCA\Passwords\Exception\Favicon\FaviconRequestException;
use OCA\Passwords\Exception\Favicon\UnexpectedResponseCodeException;
use OCA\Passwords\Helper\Time\DateTimeHelper;
use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCA\Passwords\Services\NotificationService;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class BetterIdeaHelper
 */
class BestIconProvider extends AbstractFaviconProvider {

    const BESTICON_SHARED_INSTANCE = 'https://icons.passwordsapp.org/icon';
    const BESTICON_CONFIG_KEY      = 'service/favicon/bi/url';
    const BESTICON_COUNTER_KEY     = 'service/favicon/bi/counter';
    const BESTICON_INSTANCE_LIMIT  = 250;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var DateTimeHelper
     */
    protected DateTimeHelper $dateTime;

    /**
     * @var AdminUserHelper
     */
    protected AdminUserHelper $adminHelper;

    /**
     * @var NotificationService
     */
    protected NotificationService $notifications;

    /**
     * @var string
     */
    protected string $prefix = HelperService::FAVICON_BESTICON;

    /**
     * @var array
     */
    protected array $colors
        = [
            '1abc9c',
            '16a085',
            '2ecc71',
            '27ae60',
            '3498db',
            '2980b9',
            '9b59b6',
            '8e44ad',
            'f1c40f',
            'f39c12',
            'e67e22',
            'd35400',
            'e74c3c',
            'c0392b'
        ];

    /**
     * BestIconProvider constructor.
     *
     * @param DateTimeHelper       $dateTime
     * @param ConfigurationService $config
     * @param LoggerInterface      $logger
     * @param HelperService        $helperService
     * @param AdminUserHelper      $adminHelper
     * @param IClientService       $requestService
     * @param FileCacheService     $fileCacheService
     * @param NotificationService  $notificationService
     */
    public function __construct(
        DateTimeHelper        $dateTime,
        ConfigurationService  $config,
        LoggerInterface       $logger,
        HelperService         $helperService,
        AdminUserHelper       $adminHelper,
        IClientService        $requestService,
        FileCacheService      $fileCacheService,
        NotificationService   $notificationService
    ) {
        $this->config        = $config;
        $this->dateTime      = $dateTime;
        $this->adminHelper   = $adminHelper;
        $this->notifications = $notificationService;

        parent::__construct($helperService, $logger, $fileCacheService, $requestService);
    }

    /**
     * @param string $domain
     * @param string $protocol
     *
     * @return array
     */
    protected function getRequestData(string $domain, string $protocol = 'https'): array {
        $fallbackColor = substr($this->stringToColor($domain), 1);
        $serviceUrl    = $this->config->getAppValue(self::BESTICON_CONFIG_KEY, '');
        if(empty($serviceUrl)) $serviceUrl = $this->getSharedInstanceUrl();

        return [
            "{$serviceUrl}?size=16..128..256&fallback_icon_color={$fallbackColor}&url={$protocol}://{$domain}&formats=png,ico,gif,jpg",
            []
        ];
    }

    /**
     * @param string $domain
     *
     * @return null|string
     * @throws FaviconRequestException
     * @throws UnexpectedResponseCodeException
     */
    protected function getFaviconData(string $domain): ?string {
        [$uri, $options] = $this->getRequestData($domain);

        try {
            $data = $this->executeRequest($uri, $options);
            if(!empty($data)) return $data;
        } catch(Throwable $e) {
        }

        [$uri, $options] = $this->getRequestData($domain, 'http');

        return $this->executeRequest($uri, $options);
    }

    /**
     * @return string
     */
    protected function getSharedInstanceUrl(): string {
        $this->checkSharedInstanceLimits();

        return self::BESTICON_SHARED_INSTANCE;
    }

    /**
     *
     */
    protected function checkSharedInstanceLimits(): void {
        try {
            $currentWeek = $this->dateTime->getInternationalWeek();
            $this->config->clearCache();

            [$week, $count, $notified] = explode(':', $this->config->getAppValue(self::BESTICON_COUNTER_KEY, '0:0:0'));
            if(intval($week) !== $currentWeek) {
                $count    = 0;
                $notified = '0';
            }

            $count++;
            if($count >= self::BESTICON_INSTANCE_LIMIT && $notified === '0') {
                $notified = '1';
                foreach($this->adminHelper->getAdmins() as $admin) {
                    $this->notifications->sendBesticonApiNotification($admin->getUID());
                }
            }
            $this->config->setAppValue(self::BESTICON_COUNTER_KEY, "{$currentWeek}:{$count}:{$notified}");
        } catch(Throwable $e) {
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function stringToColor(string $string): string {
        $max    = count($this->colors);
        $number = array_sum(str_split(hexdec(crc32($string)), 2));

        while($number >= $max) {
            $number -= $max;
        }

        return '#'.$this->colors[ $number ];
    }
}