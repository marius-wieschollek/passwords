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
            'a' => '4',
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
            'A' => '@',
            'a' => '@',
            's' => '$',
            'S' => '$',
            'i' => '!',
            'I' => '!'
        ];

    const ADD_NUMBERS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
    const ADD_SPECIAL = ['!', '$', '%', '&', '?', '#', '='];

    /**
     * @param string $string
     * @param int    $amount
     * @param bool   $addNumbers
     * @param bool   $addSpecial
     *
     * @return string
     */
    public function addSpecialCharacters(string $string, int $amount, bool $addNumbers, bool $addSpecial): string {
        if($addSpecial) {
            $string = $this->replaceCharacters($string, $amount, self::REPLACE_SPECIAL, self::ADD_SPECIAL);
        }
        if($addNumbers) {
            $string = $this->replaceCharacters($string, $amount, self::REPLACE_NUMBERS, self::ADD_NUMBERS);
        }

        return $string;
    }

    /**
     * @param string   $string
     * @param int      $amount
     * @param string[] $replacements
     * @param string[] $additions
     *
     * @return string
     */
    protected function replaceCharacters(string $string, int $amount, array $replacements, array $additions) {
        $positions = $this->getCharacterPositions($string, $replacements);
        $total     = count($positions);

        try {
            if($total < $amount) {
                $result = $this->replaceAllMatches($string, $positions);

                return $this->addExtraCharacters($result, $additions, $amount - $total);
            }

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
        $characterPositions = [];
        foreach($characters as $character => $replacement) {
            $offset = 0;
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
     * @throws Exception
     */
    protected function replaceRandomMatches(string $string, int $amount, array $positions): string {
        $characters = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);

        for($i = 0; $i < $amount; $i++) {
            $random = random_int(0, count($positions) - 1);
            [$replacement, $position] = array_splice($positions, $random, 1)[0];
            if(!is_numeric($characters[ $position ])) {
                $characters[ $position ] = $replacement;
            }
        }

        return implode($characters);
    }

    /**
     * @param string $result
     * @param array  $additions
     * @param int    $total
     *
     * @return string
     * @throws Exception
     */
    protected function addExtraCharacters(string $result, array $additions, int $total): string {
        $length = count($additions) - 1;

        for($i = 0; $i < $total; $i++) {
            $position  = random_int(0, $length);
            $character = $additions[ $position ];

            if($i % 2 == 0) {
                $result = $character.$result;
            } else {
                $result .= $character;
            }
        }

        return $result;
    }
}