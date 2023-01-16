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

namespace OCA\Passwords\Encryption\Object;

interface SseV3KeyProviderInterface {

    /**
     * Returns if the key provider is ready to provide encryption keys
     *
     * @return bool
     * @since 2023.2.0
     */
    public function isAvailable(): bool;

    /**
     * Get the encryption key for the given $userId.
     * The key must be at least 32 characters long and consist of at least 8 different characters
     *
     * @param string $userId
     *
     * @since 2023.2.0
     * @return string
     */
    public function getUserKey(string $userId): string;
}