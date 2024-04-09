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

namespace OCA\Passwords\Provider\SecurityCheck;

use Exception;
use OCA\Passwords\Helper\SecurityCheck\UserRulesSecurityCheck;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;
use OCP\Http\Client\IClientService;
use Throwable;

/**
 * Class BigDbPlusHibpSecurityCheckProvider
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class BigDbPlusHibpSecurityCheckProvider extends AbstractSecurityCheckProvider {

    const PASSWORD_DB = 'bigdb+hibp';

    /**
     * BigDbPlusHibpSecurityCheckProvider constructor.
     *
     * @param LoggingService                  $logger
     * @param IClientService                  $httpClientService
     * @param FileCacheService                $fileCacheService
     * @param UserRulesSecurityCheck          $userRulesCheck
     * @param HaveIBeenPwnedProvider          $hibpSecurityCheck
     * @param ConfigurationService            $configurationService
     * @param BigLocalDbSecurityCheckProvider $localSecurityCheck
     */
    public function __construct(
        LoggingService                            $logger,
        IClientService                            $httpClientService,
        FileCacheService                          $fileCacheService,
        UserRulesSecurityCheck                    $userRulesCheck,
        ConfigurationService                      $configurationService,
        protected HaveIBeenPwnedProvider          $hibpSecurityCheck,
        protected BigLocalDbSecurityCheckProvider $localSecurityCheck
    ) {
        parent::__construct($logger, $httpClientService, $fileCacheService, $userRulesCheck, $configurationService);
    }

    /**
     * @inheritdoc
     *
     * @param string $hash
     *
     * @return bool
     * @throws Exception
     */
    public function isHashSecure(string $hash): bool {
        return $this->localSecurityCheck->isHashSecure($hash) && $this->hibpSecurityCheck->isHashSecure($hash);
    }

    /**
     * @inheritdoc
     *
     * @param string $range
     *
     * @return array
     */
    public function getHashRange(string $range): array {
        return array_unique(
            array_merge(
                $this->localSecurityCheck->getHashRange($range),
                $this->hibpSecurityCheck->getHashRange($range)
            )
        );
    }

    /**
     * Refresh the locally stored database with password hashes
     *
     * @return void
     * @throws Throwable
     */
    function updateDb(): void {
        $this->localSecurityCheck->updateDb();
        $this->config->setAppValue(self::CONFIG_DB_TYPE, static::PASSWORD_DB);
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool {
        return $this->localSecurityCheck->isAvailable() && $this->hibpSecurityCheck->isAvailable();
    }

    /**
     * @inheritdoc
     */
    function dbUpdateRequired(): bool {
        return !$this->localSecurityCheck->isLocalDbValid() || parent::dbUpdateRequired();
    }
}