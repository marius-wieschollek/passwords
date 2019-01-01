<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Words;

/**
 * Class AbstractWordsHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
abstract class AbstractWordsHelper {

    /**
     * @param int $strength
     *
     * @return array
     */
    abstract public function getWords(int $strength): array;

    /**
     * Whether or not this service can be used in the current environment
     *
     * @return bool
     */
    abstract public static function isAvailable(): bool;

    /**
     * @param array $words
     *
     * @return bool
     */
    protected function isWordsArrayValid(array $words): bool {
        $map = array_map('strlen', $words);
        $max = max($map);
        $min = min($map);

        return $min > 3 && $max < 12;
    }
}