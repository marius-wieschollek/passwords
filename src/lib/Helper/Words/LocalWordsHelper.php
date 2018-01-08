<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 13:41
 */

namespace OCA\Passwords\Helper\Words;

use Exception;

/**
 * Class LocalWordsHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
class LocalWordsHelper extends AbstractWordsHelper {

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
    protected $langCode;

    /**
     * LocalWordsHelper constructor.
     *
     * @param string $langCode
     */
    public function __construct(string $langCode) {
        $this->langCode = $langCode;
    }

    /**
     * @param int $strength
     *
     * @return array
     * @throws Exception
     */
    public function getWords(int $strength): array {
        $length = $strength == 1 ? 2:$strength;
        $file   = $this->getWordsFile();

        $retires = 0;
        while($retires < 10) {
            exec("shuf -n {$length} {$file}", $result, $code);

            if($code == 0) {
                $max = max(array_map('strlen', $result));
                $min = min(array_map('strlen', $result));

                if($min > 3 && $max < 12) return $result;
            }
            $retires++;
        }

        return [];
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function getWordsFile(): string {
        $wordsFile = '';
        switch($this->langCode) {
            case 'de':
                $wordsFile = self::WORDS_DE;
                break;
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
                $wordsFile = self::WORDS_ES;
                break;
            case 'es_MX':
                $wordsFile = self::WORDS_ES;
                break;
            case 'es_AR':
                $wordsFile = self::WORDS_ES;
                break;
            case 'pt':
                $wordsFile = self::WORDS_PT;
                break;
            case 'pt_BR':
                $wordsFile = self::WORDS_PT;
                break;
        }

        if(!is_file($wordsFile)) {
            if(is_file(self::WORDS_DEFAULT)) {
                return self::WORDS_DEFAULT;
            }

            throw new Exception('No local words file found. Install a words file in '.self::WORDS_DEFAULT);
        }

        return $wordsFile;
    }
}