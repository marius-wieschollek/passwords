<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Words;

use Exception;

/**
 * Class RandomCharactersHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
class RandomCharactersHelper extends AbstractWordsHelper {

    const CHARACTER_LIST = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
    const CHARACTERS_DE  = 'ÄäÖöÜüß';
    const CHARACTERS_FR  = 'ÉèÊéÈêÂâÀàÔôÚúÛû';
    const CHARACTERS_IT  = 'ÀàÈèÚú';
    const CHARACTERS_ES  = 'ÁáÉéÍíÑñÓóÚú';
    const CHARACTERS_PT  = 'ÁáÂâÀàÃãÇçÉéÊêÍíÔôÓôÕõÚÚ';
    const NUMBERS        = '0123456789';
    const SPECIAL        = '!?&%/()[]{}$€@-_';

    /**
     * @var string
     */
    protected $langCode;

    /**
     * LocalWordsHelper constructor.
     *
     * @param string $langCode
     */
    public function __construct(string $langCode) {
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
        $characters = $this->getCharacterString();
        $strength   += 2;
        $length     = $strength === 3 ? 4:$strength;

        for($i = 0; $i < $length; $i++) {
            $string = '';
            for($j = 0; $j < $strength; $j++) {
                try {
                    $pos = random_int(0, mb_strlen($characters) - 1);
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
     * @return string
     */
    protected function getCharacterString(): string {
        $characters = self::CHARACTER_LIST;
        switch($this->langCode) {
            case 'de':
                return $characters.self::CHARACTERS_DE;
            case 'fr':
                return $characters.self::CHARACTERS_FR;
            case 'it':
                return $characters.self::CHARACTERS_IT;
            case 'es':
                return $characters.self::CHARACTERS_ES;
            case 'pt':
                return $characters.self::CHARACTERS_PT;
        }

        return $characters;
    }

    /**
     * @inheritdoc
     */
    public static function isAvailable(): bool {
        try {
            random_int(1, 10);

            return extension_loaded('mbstring');
        } catch(\Exception $e) {
            return false;
        }
    }

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
                $charPos       = random_int(0, mb_strlen($characters) - 1);
                $character     = mb_substr($characters, $charPos, 1);
                $pos           = random_int(0, count($parts) - 1);
                $parts[ $pos ] = $character;
            } catch(\Exception $e) {
                $i--;
            }
        }

        return implode($parts);
    }
}