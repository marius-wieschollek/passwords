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

use Exception;
use Random\Randomizer;

/**
 * Class RandomCharactersProvider
 *
 * @package OCA\Passwords\Helper\Words
 */
class RandomCharactersProvider extends AbstractWordsProvider {

    const CHARACTER_LIST = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
    const NUMBERS        = '0123456789';
    const SPECIAL        = '!?&%/()[]{}$€@-_';

    /**
     * @var string
     */
    protected string $langCode;

    /**
     * LocalWordsProvider constructor.
     *
     * @param string $langCode
     */
    public function __construct(protected Randomizer $randomizer, string $langCode) {
        $this->langCode = substr($langCode, 0, 2);
    }

    /**
     * @param int  $strength
     *
     * @param bool $addNumbers
     * @param bool $addSpecial
     *
     * @return array|null
     */
    public function getWords(int $strength, bool $addNumbers, bool $addSpecial): ?array {
        $words      = [];
        $characters = self::CHARACTER_LIST;
        $strength   += 3;
        $length     = $strength === 3 ? 4:$strength;

        for($i = 0; $i < $length; $i++) {
            $string = '';
            for($j = 0; $j < $strength; $j++) {
                try {
                    $pos    = $this->randomizer->getInt(0, mb_strlen($characters) - 1);
                    $string .= mb_substr($characters, $pos, 1);
                } catch(Exception $e) {
                    $j--;
                }
            }
            $words[] = $string;
        }

        return [
            'words'    => $words,
            'password' => $this->wordsArrayToPassword($words, $strength, $addNumbers, $addSpecial)
        ];
    }

    /**
     * @inheritdoc
     */
    public function isAvailable(): bool {
        try {
            $this->randomizer->getInt(1, 10);

            return extension_loaded('mbstring');
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * @param array $words
     * @param int   $strength
     * @param bool  $addNumbers
     * @param bool  $addSpecial
     *
     * @return string
     */
    protected function wordsArrayToPassword(array $words, int $strength = 4, bool $addNumbers = true, bool $addSpecial = true): string {
        $password = implode($words);

        if($addNumbers) {
            $password = $this->addSpecialCharacters($password, $strength * 2, self::NUMBERS);
        }

        if($addSpecial) {
            $password = $this->addSpecialCharacters($password, $strength * 2, self::SPECIAL);
        }

        return $password;
    }

    /**
     * @param string $string
     * @param int    $amount
     * @param string $characters
     *
     * @return string
     */
    protected function addSpecialCharacters(string $string, int $amount, string $characters): string {
        $parts = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);

        for($i = 0; $i < $amount; $i++) {
            try {
                $charPos       = $this->randomizer->getInt(0, mb_strlen($characters) - 1);
                $character     = mb_substr($characters, $charPos, 1);
                $pos           = $this->randomizer->getInt(0, count($parts) - 1);
                $parts[ $pos ] = $character;
            } catch(Exception $e) {
                $i--;
            }
        }

        return implode($parts);
    }
}