<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 07.01.18
 * Time: 20:28
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;

/**
 * Class BigDbPlusHibpSecurityCheckHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
class BigDbPlusHibpSecurityCheckHelper extends AbstractSecurityCheckHelper {

    const PASSWORD_DB = 'bigdb+hibp';

    /**
     * @var BigLocalDbSecurityCheckHelper
     */
    protected $localSecurityCheck;

    /**
     * @var HaveIBeenPwnedHelper
     */
    protected $hibpSecurityCheck;

    /**
     * BigDbPlusHibpSecurityCheckHelper constructor.
     *
     * @param FileCacheService              $fileCacheService
     * @param ConfigurationService          $configurationService
     * @param LoggingService                $logger
     * @param BigLocalDbSecurityCheckHelper $localSecurityCheck
     * @param HaveIBeenPwnedHelper          $hibpSecurityCheck
     */
    public function __construct(
        LoggingService $logger,
        FileCacheService $fileCacheService,
        HaveIBeenPwnedHelper $hibpSecurityCheck,
        ConfigurationService $configurationService,
        BigLocalDbSecurityCheckHelper $localSecurityCheck
    ) {
        parent::__construct($logger, $fileCacheService, $configurationService);
        $this->localSecurityCheck = $localSecurityCheck;
        $this->hibpSecurityCheck  = $hibpSecurityCheck;
    }

    /**
     * @inheritdoc
     *
     * @param string $hash
     *
     * @return bool
     */
    public function isHashSecure(string $hash): bool {
        return $this->localSecurityCheck->isHashSecure($hash) && $this->hibpSecurityCheck->isHashSecure($hash);
    }

    /**
     * Refresh the locally stored database with password hashes
     *
     * @return void
     */
    function updateDb(): void {
        $this->localSecurityCheck->updateDb();
        $this->config->setAppValue(self::CONFIG_DB_TYPE, static::PASSWORD_DB);
    }
}