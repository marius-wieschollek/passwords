<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Encryption\Challenge;

use OCA\Passwords\Db\Challenge;

/**
 * Interface ChallengeEncryptionInterface
 *
 * @package OCA\Passwords\Encryption\Challenge
 */
interface ChallengeEncryptionInterface {

    /**
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param Challenge $challenge
     *
     * @return Challenge
     */
    public function encryptChallenge(Challenge $challenge): Challenge;

    /**
     * @param Challenge $challenge
     *
     * @return Challenge
     */
    public function decryptChallenge(Challenge $challenge): Challenge;
}