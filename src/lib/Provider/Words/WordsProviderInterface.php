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

namespace OCA\Passwords\Provider\Words;

interface WordsProviderInterface {
    /**
     * @param int  $strength
     * @param bool $addNumbers
     * @param bool $addSpecial
     *
     * @return array|null
     */
    public function getWords(int $strength, bool $addNumbers, bool $addSpecial): ?array;

    /**
     * Whether or not this service can be used in the current environment
     *
     * @return bool
     */
    public function isAvailable(): bool;

}