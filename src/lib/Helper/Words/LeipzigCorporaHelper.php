<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Words;

use OCP\Http\Client\IClientService;

/**
 * Class LeipzigCorporaHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
class LeipzigCorporaHelper extends AbstractWordsHelper {

    const SERVICE_URL = 'http://api.corpora.uni-leipzig.de/ws/';

    /**
     * @var bool
     */
    protected static $isAvailable = false;

    /**
     * @var string
     */
    protected $langCode;

    /**
     * @var SpecialCharacterHelper
     */
    protected $specialCharacters;

    /**
     * @var IClientService
     */
    protected $httpClientService;

    /**
     * LocalWordsHelper constructor.
     *
     * @param string                 $langCode
     * @param SpecialCharacterHelper $specialCharacters
     */
    public function __construct(SpecialCharacterHelper $specialCharacters, IClientService $httpClientService, string $langCode) {
        $this->langCode          = $langCode;
        $this->specialCharacters = $specialCharacters;
        $this->httpClientService = $httpClientService;
    }

    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function getWords(int $strength, bool $addNumbers, bool $addSpecial): ?array {
        $corpora    = $this->selectCorpora();
        $httpClient = $this->httpClientService->newClient();
        $response   = $httpClient->get(static::SERVICE_URL.'words/'.$corpora.'/randomword/?limit=40');
        $data       = json_decode($response->getBody());

        $minLength = 16 + $strength * 4;
        $curLength = 0;
        $words     = [];
        foreach($data as $word) {
            $actualWord = preg_replace('/\W+/u', '', ucwords($word->word));
            if(is_numeric($actualWord)) continue;

            $words[]   = $actualWord;
            $curLength += strlen($actualWord);

            if($curLength >= $minLength && count($words) > $strength) {
                break;
            }
        }

        if($curLength < $minLength || count($words) <= $strength) {
            throw new \Exception('Unable to find words matching requirements');
        }

        return [
            'words'    => $words,
            'password' => $this->wordsArrayToPassword($words, $strength, $addNumbers, $addSpecial)
        ];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function selectCorpora(): string {
        $httpClient = $this->httpClientService->newClient();
        $response   = $httpClient->get(static::SERVICE_URL.'corpora/availableCorpora');
        $data       = json_decode($response->getBody());
        $prefixes   = $this->mapLanguageCode();

        foreach($prefixes as $prefix) {
            $corpora = [];
            foreach($data as $corpus) {
                if(substr($corpus->corpusName, 0, 3) === $prefix) {
                    $corpora[] = $corpus->corpusName;
                }
            }

            $matches = count($corpora);
            if($matches > 0) {
                $selectedCorpora = random_int(0, $matches - 1);

                return $corpora[ $selectedCorpora ];
            }
        }

        throw new \Exception('Unable to find corpora');
    }

    /**
     * @return array
     */
    protected function mapLanguageCode(): array {
        $lang = substr($this->langCode, 0, 2);

        $prefixes = [];
        if($lang === 'de') {
            $prefixes[] = 'deu';
        } else if($lang === 'fr') {
            $prefixes[] = 'fra';
        } else if($lang === 'it') {
            $prefixes[] = 'ita';
        } else if($lang === 'es') {
            $prefixes[] = 'spa';
        } else if($lang === 'pt') {
            $prefixes[] = 'por';
        } else if($lang === 'nl') {
            $prefixes[] = 'nld';
        } else if($lang === 'da') {
            $prefixes[] = 'dan';
        } else if($lang === 'cs') {
            $prefixes[] = 'ces';
        } else if($lang === 'pl') {
            $prefixes[] = 'pol';
        }
        $prefixes[] = 'eng';

        return $prefixes;
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
     * @inheritDoc
     */
    public static function isAvailable(): bool {
        if(static::$isAvailable) return static::$isAvailable;

        try {
            $client   = \OC::$server->getHTTPClientService()->newClient();
            $response = $client->head(LeipzigCorporaHelper::SERVICE_URL);

            static::$isAvailable = $response->getStatusCode() === 200;

            return static::$isAvailable;
        } catch(\Exception $e) {
            return false;
        }
    }
}