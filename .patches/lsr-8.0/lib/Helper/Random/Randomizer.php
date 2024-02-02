<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Random;

use Random\RandomException;

class Randomizer {

    /**
     * @throws RandomException
     */
    public function getBytes(int $length): string {
        return random_bytes($length);
    }

    /**
     * @throws RandomException
     */
    public function getInt(int $min, int $max): int {
        return random_int($min, $max);
    }
}