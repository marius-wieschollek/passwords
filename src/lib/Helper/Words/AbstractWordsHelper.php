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
}