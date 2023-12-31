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

use OCA\Passwords\Helper\SecurityCheck\UserRulesSecurityCheck;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\LoggingService;
use OCP\Http\Client\IClientService;
use Throwable;

/**
 * Class AbstractSecurityCheckProvider
 *
 * @package OCA\Passwords\Helper\SecurityCheck
 */
abstract class AbstractSecurityCheckProvider implements SecurityCheckProviderInterface {

    const PASSWORD_DB          = 'none';
    const HASH_FILE_KEY_LENGTH = 3;
    const CONFIG_DB_TYPE       = 'passwords/localdb/type';

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
     * AbstractSecurityCheckProvider constructor.
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
}