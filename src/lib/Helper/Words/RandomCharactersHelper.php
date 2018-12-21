<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Words;

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
     * @param int $strength
     *
     * @return array
     * @throws \Exception
     */
    public function getWords(int $strength): array {
        $words      = [];
        $characters = $this->getCharacterString();
        $strength   += 2;
        $length     = $strength === 3 ? 4:$strength;

        for($i = 0; $i < $length; $i++) {
            $string = '';
            for($j = 0; $j < $strength; $j++) {
                $pos    = random_int(0, mb_strlen($characters) - 1);
                $string .= mb_substr($characters, $pos, 1);
            }
            $words[] = $string;
        }

        return $words;
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
}