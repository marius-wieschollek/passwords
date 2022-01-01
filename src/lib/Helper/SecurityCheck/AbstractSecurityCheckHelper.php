<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use Exception;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;
use OCP\Http\Client\IClientService;
use Throwable;

/**
 * Class AbstractSecurityCheckHelper
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
abstract class AbstractSecurityCheckHelper {

    const PASSWORD_DB          = 'none';
    const HASH_FILE_KEY_LENGTH = 3;
    const CONFIG_DB_ENCODING   = 'passwords/localdb/encoding';
    const CONFIG_DB_TYPE       = 'passwords/localdb/type';

    const STATUS_BREACHED    = 'BREACHED';
    const STATUS_OUTDATED    = 'OUTDATED';
    const STATUS_DUPLICATE   = 'DUPLICATE';
    const STATUS_GOOD        = 'GOOD';
    const STATUS_NOT_CHECKED = 'NOT_CHECKED';
    const LEVEL_OK           = 0;
    const LEVEL_WEAK         = 1;
    const LEVEL_BAD          = 2;
    const LEVEL_UNKNOWN      = 3;

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var LoggingService
     */
    protected LoggingService $logger;

    /**
     * @var IClientService
     */
    protected IClientService $httpClientService;

    /**
     * @var UserRulesSecurityCheck
     */
    protected UserRulesSecurityCheck $userRulesCheck;

    /**
     * @var array
     */
    protected array $hashStatusCache = [];

    /**
     * AbstractSecurityCheckHelper constructor.
     *
     * @param LoggingService         $logger
     * @param IClientService         $httpClientService
     * @param FileCacheService       $fileCacheService
     * @param UserRulesSecurityCheck $userRulesCheck
     * @param ConfigurationService   $configurationService
     */
    public function __construct(
        LoggingService         $logger,
        IClientService         $httpClientService,
        FileCacheService       $fileCacheService,
        UserRulesSecurityCheck $userRulesCheck,
        ConfigurationService   $configurationService
    ) {
        $this->fileCacheService  = $fileCacheService->getCacheService($fileCacheService::PASSWORDS_CACHE);
        $this->config            = $configurationService;
        $this->logger            = $logger;
        $this->userRulesCheck    = $userRulesCheck;
        $this->httpClientService = $httpClientService;
    }

    /**
     * Checks if the given revision is secure and complies with the users individual password standards
     * No all user password standards can be checked server side
     * 0 = secure, 1 = user standard violation, 2 = compromised
     *
     * @param PasswordRevision $revision
     *
     * @return array
     * @throws Exception
     */
    public function getRevisionSecurityLevel(PasswordRevision $revision): array {
        if(empty($revision->getHash())) return [self::LEVEL_UNKNOWN, self::STATUS_NOT_CHECKED];
        if(!$this->isHashSecure($revision->getHash())) return [self::LEVEL_BAD, self::STATUS_BREACHED];

        $userRules = $this->userRulesCheck->getRevisionSecurityLevel($revision);
        if($userRules !== null) return $userRules;

        return [self::LEVEL_OK, self::STATUS_GOOD];
    }

    /**
     * Checks if the given password is known to be insecure
     *
     * @param string $password
     *
     * @return bool
     */
    public function isPasswordSecure(string $password): bool {
        return $this->isHashSecure(sha1($password));
    }

    /**
     * Checks if the given hash belongs to an insecure password
     *
     * @param string $hash
     *
     * @return bool
     */
    public function isHashSecure(string $hash): bool {
        if(empty($hash)) return false;

        if(!isset($this->hashStatusCache[ $hash ])) {
            $hashes                         = $this->readPasswordsFile($hash);
            $this->hashStatusCache[ $hash ] = !$this->checkForHashInHashes($hashes, $hash);
        }

        return $this->hashStatusCache[ $hash ];
    }

    /**
     * Checks if the local password database needs to be updated
     *
     * @return bool
     */
    function dbUpdateRequired(): bool {
        $installedType = $this->config->getAppValue(self::CONFIG_DB_TYPE);

        return $installedType !== static::PASSWORD_DB;
    }

    /**
     * @param string $hash
     *
     * @return array
     */
    protected function readPasswordsFile(string $hash): array {
        $file = $this->getPasswordsFileName($hash);
        if(!$this->fileCacheService->hasFile($file)) return [];

        try {
            $data = $this->fileCacheService->getFile($file)->getContent();
            if(extension_loaded('zlib')) $data = gzuncompress($data);
        } catch(Throwable $e) {
            $this->logger->logException($e);

            return [];
        }

        $data = json_decode($data, true);

        return is_array($data) ? $data:[];
    }

    /**
     * @param string $hash
     * @param array  $hashes
     */
    protected function writePasswordsFile(string $hash, array $hashes): void {
        $file = $this->getPasswordsFileName($hash);

        $data = json_encode(array_unique($hashes));
        if(extension_loaded('zlib')) $data = gzcompress($data);

        $this->fileCacheService->putFile($file, $data);
    }

    /**
     * @param string $hash
     *
     * @return string
     */
    protected function getPasswordsFileName(string $hash): string {
        $file = substr($hash, 0, self::HASH_FILE_KEY_LENGTH).'.json';

        return extension_loaded('zlib') ? $file.'.gz':$file;
    }

    /**
     * @param $hashes
     * @param $hash
     *
     * @return bool
     */
    protected function checkForHashInHashes($hashes, $hash): bool {
        $length = strlen($hash);
        if($length === 40) {
            return in_array($hash, $hashes);
        } else {
            foreach($hashes as $current) {
                if(substr($current, 0, $length) === $hash) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Refresh the locally stored database with password hashes
     *
     * @return void
     */
    abstract function updateDb(): void;

    /**
     * Refresh the locally stored database with password hashes
     *
     * @return void
     */
    abstract function isAvailable(): bool;
}