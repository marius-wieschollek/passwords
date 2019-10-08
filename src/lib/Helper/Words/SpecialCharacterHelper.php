<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Words;

use Exception;

/**
 * Class SpecialCharacterHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
class SpecialCharacterHelper {

    const REPLACE_NUMBERS
        = [
            'A' => '4',
            'e' => '3',
            'E' => '3',
            'l' => '1',
            'i' => '1',
            'I' => '1',
            'o' => '0',
            'O' => '0',
            's' => '5',
            'S' => '5',
            't' => '7',
            'T' => '7'
        ];

    const REPLACE_SPECIAL
        = [
            'e' => '€',
            'E' => '€',
            'a' => '@',
            's' => '$',
            'S' => '$',
            'i' => '!',
            'I' => '!'
        ];

    /**
     * @param string $string
     * @param int    $amount
     * @param bool   $addNumbers
     * @param bool   $addSpecial
     *
     * @return string
     */
    public function addSpecialCharacters(string $string, int $amount, bool $addNumbers, bool $addSpecial): string {
        if($addNumbers) {
            $string = $this->replaceCharacters($string, $amount, self::REPLACE_NUMBERS);
        }
        if($addSpecial) {
            $string = $this->replaceCharacters($string, $amount, self::REPLACE_SPECIAL);
        }

        return $string;
    }

    /**
     * @param string $string
     * @param int    $amount
     * @param array  $replacements
     *
     * @return string
     */
    protected function replaceCharacters(string $string, int $amount, array $replacements) {
        $positions = $this->getCharacterPositions($string, $replacements);

        if(count($positions) < $amount) {
            return $this->replaceAllMatches($string, $positions);
        }

        try {
            return $this->replaceRandomMatches($string, $amount, $positions);
        } catch(Exception $e) {
            return $string;
        }
    }

    /**
     * @param string $string
     * @param array  $characters
     *
     * @return array
     */
    protected function getCharacterPositions(string $string, array $characters) {
        $offset             = 0;
        $characterPositions = [];
        foreach($characters as $character => $replacement) {
            while(($pos = mb_strpos($string, $character, $offset)) !== false) {
                $offset               = $pos + 1;
                $characterPositions[] = [$replacement, $pos];
            }
        }

        return $characterPositions;
    }

    /**
     * @param string $string
     * @param array  $positions
     *
     * @return string
     */
    protected function replaceAllMatches(string $string, array $positions): string {
        $characters = preg_split('//u', $string);

        foreach($positions as $position) {
            $characters[ $position[1] ] = $position[0];
        }

        return implode($characters);
    }

    /**
     * @param string $string
     * @param int    $amount
     * @param array  $positions
     *
     * @return string
     * @throws \Exception
     */
    protected function replaceRandomMatches(string $string, int $amount, array $positions): string {
        $characters = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);

        for($i = 0; $i < $amount; $i++) {
            $random = random_int(0, count($positions) - 1);
            list($replacement, $position) = array_splice($positions, $random, 1)[0];
            if(!is_numeric($characters[ $position ])) {
                $characters[ $position ] = $replacement;
            }
        }

        return implode($characters);
    }
}