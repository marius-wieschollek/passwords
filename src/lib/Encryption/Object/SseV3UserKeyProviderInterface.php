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

interface SseV3UserKeyProviderInterface {

    public function isAvailable(): bool;

    public function getKey(string $userId): string;
}