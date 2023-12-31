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

interface SecurityCheckProviderInterface {

    /**
     * Checks if the given password is known to be insecure
     *
     * @param string $password
     *
     * @return bool
     */
    public function isPasswordSecure(string $password): bool;

    /**
     * Checks if the given hash belongs to an insecure password
     *
     * @param string $hash
     *
     * @return bool
     */
    public function isHashSecure(string $hash): bool;

    /**
     * Get all hashes of compromised passwords within the given range
     *
     * @param string $range
     *
     * @return array
     */
    public function getHashRange(string $range): array;

    /**
     * Checks if the local password database needs to be updated
     *
     * @return bool
     */
    public function dbUpdateRequired(): bool;

    /**
     * Refresh the locally stored database with password hashes
     *
     * @return void
     */
    public function updateDb(): void;

    /**
     * Refresh the locally stored database with password hashes
     *
     * @return bool
     */
    public function isAvailable(): bool;
}