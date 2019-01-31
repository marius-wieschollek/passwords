<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\SecurityCheck;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;

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

    const STATUS_BREACHED  = 'BREACHED';
    const STATUS_OUTDATED  = 'OUTDATED';
    const STATUS_DUPLICATE = 'DUPLICATE';
    const STATUS_GOOD      = 'GOOD';
    const LEVEL_OK         = 0;
    const LEVEL_WEAK       = 1;
    const LEVEL_BAD        = 2;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var UserRulesSecurityCheck
     */
    protected $userRulesCheck;

    /**
     * @var array
     */
    protected $hashStatusCache = [];

    /**
     * BigPasswordDbHelper constructor.
     *
     * @param LoggingService         $logger
     * @param UserRulesSecurityCheck $userRulesCheck
     * @param FileCacheService       $fileCacheService
     * @param ConfigurationService   $configurationService
     */
    public function __construct(
        LoggingService $logger,
        FileCacheService $fileCacheService,
        UserRulesSecurityCheck $userRulesCheck,
        ConfigurationService $configurationService
    ) {
        $this->fileCacheService = $fileCacheService->getCacheService($fileCacheService::PASSWORDS_CACHE);
        $this->config           = $configurationService;
        $this->logger           = $logger;
        $this->userRulesCheck   = $userRulesCheck;
    }

    /**
     * Checks if the given revision is secure and complies with the users individual password standards
     * No all user password standards can be checked server side
     * 0 = secure, 1 = user standard violation, 2 = compromised
     *
     * @param PasswordRevision $revision
     *
     * @return array
     * @throws \Exception
     */
    public function getRevisionSecurityLevel(PasswordRevision $revision): array {
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
        if(!isset($this->hashStatusCache[ $hash ])) {
            $hashes                         = $this->readPasswordsFile($hash);
            $this->hashStatusCache[ $hash ] = !in_array($hash, $hashes);
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

        return $installedType != static::PASSWORD_DB;
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
        } catch(\Throwable $e) {
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
     * Refresh the locally stored database with password hashes
     *
     * @return void
     */
    abstract function updateDb(): void;
}