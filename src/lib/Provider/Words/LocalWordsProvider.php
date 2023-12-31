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
use OCA\Passwords\Helper\Words\SpecialCharacterHelper;

/**
 * Class LocalWordsProvider
 *
 * @package OCA\Passwords\Helper\Words
 */
class LocalWordsProvider extends AbstractWordsProvider {

    const WORDS_DE      = '/usr/share/dict/ngerman';
    const WORDS_US      = '/usr/share/dict/american-english';
    const WORDS_GB      = '/usr/share/dict/british-english';
    const WORDS_FR      = '/usr/share/dict/french';
    const WORDS_IT      = '/usr/share/dict/italian';
    const WORDS_ES      = '/usr/share/dict/spanish';
    const WORDS_PT      = '/usr/share/dict/portuguese';
    const WORDS_DEFAULT = '/usr/share/dict/words';

    /**
     * @var string
     */
    protected string $langCode;

    /**
     * @var SpecialCharacterHelper
     */
    protected SpecialCharacterHelper $specialCharacters;

    /**
     * LocalWordsProvider constructor.
     *
     * @param string                 $langCode
     * @param SpecialCharacterHelper $specialCharacters
     */
    public function __construct(SpecialCharacterHelper $specialCharacters, string $langCode) {
        $this->langCode          = $langCode;
        $this->specialCharacters = $specialCharacters;
    }

    /**
     * @param int  $strength
     * @param bool $addNumbers
     * @param bool $addSpecial
     *
     * @return array|null
     * @throws Exception
     */
    public function getWords(int $strength, bool $addNumbers, bool $addSpecial): ?array {
        $length = $strength + 2;
        $file   = $this->getWordsFile();

        for($i = 0; $i < 24; $i++) {
            $result = [];
            @exec("shuf -n {$length} {$file}", $result, $code);

            $result = $this->sanitizeResult($result);

            if($code === 0 && $this->isWordsArrayValid($result)) {
                return [
                    'password' => $this->wordsArrayToPassword($result, $strength, $addNumbers, $addSpecial),
                    'words'    => $result
                ];
            }
        }

        return null;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getWordsFile(): string {
        $wordsFile = '';
        switch($this->langCode) {
            case 'de':
            case 'de_DE':
                $wordsFile = self::WORDS_DE;
                break;
            case 'en':
                $wordsFile = self::WORDS_US;
                break;
            case 'en_GB':
                $wordsFile = self::WORDS_GB;
                break;
            case 'fr':
                $wordsFile = self::WORDS_FR;
                break;
            case 'it':
                $wordsFile = self::WORDS_IT;
                break;
            case 'es':
            case 'es_MX':
            case 'es_AR':
                $wordsFile = self::WORDS_ES;
                break;
            case 'pt':
            case 'pt_BR':
                $wordsFile = self::WORDS_PT;
                break;
        }

        if(is_file($wordsFile) && is_readable($wordsFile)) return $wordsFile;

        return $this->getDefaultWordsFile();
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getDefaultWordsFile(): string {
        if($this->isAvailable()) return self::WORDS_DEFAULT;

        throw new Exception('No local words file found. Install a words file in '.self::WORDS_DEFAULT);
    }

    /**
     * @param array $words
     * @param int   $strength
     * @param bool  $addNumbers
     * @param bool  $addSpecial
     *
     * @return string|void
     */
    protected function wordsArrayToPassword(array $words, int $strength = 4, bool $addNumbers = true, bool $addSpecial = true): string {
        $password = parent::wordsArrayToPassword($words);

        return $this->specialCharacters->addSpecialCharacters($password, $strength * 3, $addNumbers, $addSpecial);
    }

    /**
     * @param array $words
     *
     * @return array
     */
    protected function sanitizeResult(array $words) {
        $lang = substr($this->langCode, 0, 2);

        foreach($words as &$word) {
            if($lang === 'en') {
                $word = preg_replace('/\'s$/', '', $word);
            }

            $word = preg_replace('/[0-9]/', '', $word);
        }

        return $words;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        return @is_readable(LocalWordsProvider::WORDS_DEFAULT) && @is_file(LocalWordsProvider::WORDS_DEFAULT);
    }
}