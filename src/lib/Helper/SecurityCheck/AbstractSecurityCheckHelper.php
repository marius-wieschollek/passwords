<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 16.09.17
 * Time: 22:31
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
    const HASH_FILE_KEY_LENGTH = 2;
    const CONFIG_DB_ENCODING   = 'passwords/localdb/encoding';
    const CONFIG_DB_TYPE       = 'passwords/localdb/type';
    const ENCODING_GZIP        = 'gzip';
    const ENCODING_PLAIN       = 'plain';

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
     * @var array
     */
    protected $hashStatusCache = [];

    /**
     * BigPasswordDbHelper constructor.
     *
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $configurationService
     * @param LoggingService       $logger
     */
    public function __construct(
        FileCacheService $fileCacheService,
        ConfigurationService $configurationService,
        LoggingService $logger
    ) {
        $fileCacheService->setDefaultCache($fileCacheService::PASSWORDS_CACHE);
        $this->fileCacheService = $fileCacheService;
        $this->config           = $configurationService;
        $this->logger           = $logger;
    }

    /**
     * Checks if the given revision is secure and complies with the users individual password standards
     * No all user password standards can be checked server side
     * 0 = secure, 1 = user standard violation, 2 = hacked
     *
     * @param PasswordRevision $revision
     *
     * @return int
     */
    public function getRevisionSecurityLevel(PasswordRevision $revision): int {
        if(!$this->isHashSecure($revision->getHash())) return 2;

        return 0;
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
            $file = substr($hash, 0, self::HASH_FILE_KEY_LENGTH).'.json';

            if($this->fileCacheService->hasFile($file)) {
                $data = $this->fileCacheService->getFile($file)->getContent();

                if($this->config->getAppValue(self::CONFIG_DB_ENCODING) === self::ENCODING_GZIP) $data = gzuncompress($data);

                $this->hashStatusCache[ $hash ] = !in_array($hash, json_decode($data, true));
            } else {
                $this->hashStatusCache[ $hash ] = true;
            }
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
     * Refresh the locally stored database with password hashes
     *
     * @return void
     */
    abstract function updateDb(): void;
}