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

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Helper\SecurityCheck\PasswordDatabaseUpdateHelper;
use OCA\Passwords\Helper\SecurityCheck\UserRulesSecurityCheck;
use OCA\Passwords\Provider\SecurityCheck\SecurityCheckProviderInterface;

/**
 * Class PasswordSecurityCheckService
 *
 * Provides methods to check the security level of a password or password hash
 * and update the local database of password hashes.
 */
class PasswordSecurityCheckService {

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
     * @param SecurityCheckProviderInterface $securityCheckProvider
     * @param UserRulesSecurityCheck         $userRulesSecurityCheck
     * @param PasswordDatabaseUpdateHelper   $databaseUpdateHelper
     */
    public function __construct(
        protected SecurityCheckProviderInterface $securityCheckProvider,
        protected UserRulesSecurityCheck         $userRulesSecurityCheck,
        protected PasswordDatabaseUpdateHelper   $databaseUpdateHelper
    ) {
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

        $userRules = $this->userRulesSecurityCheck->getRevisionSecurityLevel($revision);
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
        return $this->securityCheckProvider->isPasswordSecure($password);
    }

    /**
     * Checks if the given hash belongs to an insecure password
     *
     * @param string $hash
     *
     * @return bool
     */
    public function isHashSecure(string $hash): bool {
        return $this->securityCheckProvider->isHashSecure($hash);
    }

    /**
     * Get all hashes of compromised passwords within the given range
     *
     * @param string $range
     *
     * @return array
     */
    public function getHashRange(string $range): array {
        if(strlen($range) === 40) {
            return $this->isHashSecure($range) ? []:[$range];
        }

        return $this->securityCheckProvider->getHashRange($range);
    }

    /**
     * Refresh the locally stored database with password hashes
     *
     * @return bool
     */
    public function updateDb(): bool {
        return $this->databaseUpdateHelper->updateDb();
    }
}