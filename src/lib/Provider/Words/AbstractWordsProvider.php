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

/**
 * Class AbstractWordsProvider
 *
 * @package OCA\Passwords\Helper\Words
 */
abstract class AbstractWordsProvider implements WordsProviderInterface {

    /**
     * @param array $words
     *
     * @return bool
     */
    protected function isWordsArrayValid(array $words): bool {
        $map = array_map('strlen', $words);
        $max = max($map);
        $min = min($map);

        return $min > 5 && $max < 13;
    }

    /**
     * @param array $words
     *
     * @return string
     */
    protected function wordsArrayToPassword(array $words): string {
        $words = array_map('ucfirst', $words);
        return implode('', $words);
    }
}