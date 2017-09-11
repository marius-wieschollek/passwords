<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 13:41
 */

namespace OCA\Passwords\Helper\Words;

use OCA\Passwords\Exception\ApiException;

/**
 * Class LocalWordsHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
class LocalWordsHelper extends AbstractWordsHelper {

    /**
     * @param int $strength
     *
     * @return array
     */
    protected function getServiceOptions(int $strength): array {
        return ['length' => $strength == 1 ? 2:$strength];
    }

    /**
     * @return string
     * @throws ApiException
     */
    protected function getWordsUrl(): string {
        $langCode = \OC::$server->getL10N('core')->getLanguageCode();

        $wordsFile = '';
        switch ($langCode) {
            case 'de':
                $wordsFile = '/usr/share/dict/ngerman';
                break;
            case 'de_DE':
                $wordsFile = '/usr/share/dict/ngerman';
                break;
            case 'en':
                $wordsFile = '/usr/share/dict/american-english';
                break;
            case 'en_GB':
                $wordsFile = '/usr/share/dict/british-english';
                break;
            case 'fr':
                $wordsFile = '/usr/share/dict/french';
                break;
            case 'it':
                $wordsFile = '/usr/share/dict/italian';
                break;
            case 'es':
                $wordsFile = '/usr/share/dict/spanish';
                break;
            case 'es_MX':
                $wordsFile = '/usr/share/dict/spanish';
                break;
            case 'es_AR':
                $wordsFile = '/usr/share/dict/spanish';
                break;
            case 'pt':
                $wordsFile = '/usr/share/dict/portuguese';
                break;
            case 'pt_BR':
                $wordsFile = '/usr/share/dict/portuguese';
                break;
        }

        if(!is_file($wordsFile)) {
            if(is_file('/usr/share/dict/words')) {
                return '/usr/share/dict/words';
            }


            \OC::$server->getLogger()->error('No local words file found. Install a words file in /usr/share/dict/words');

            throw new ApiException('Incorrect Words API Configuration');
        }

        return $wordsFile;
    }

    /**
     * @param string $url
     * @param array  $options
     *
     * @return string
     */
    protected function getHttpRequest(string $url, array $options = []) {
        $retires = 0;
        while ($retires < 5) {
            exec("shuf -n {$options['length']} {$url}", $result, $code);

            if($code == 0) {
                return implode(' ', $result);
            }
            $retires++;
        }

        return '';
    }
}