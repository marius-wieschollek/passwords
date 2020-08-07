<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Words;

use OCA\Passwords\Helper\Http\RequestHelper;
use OCP\Http\Client\IClientService;

/**
 * Class SnakesWordsHelper
 *
 * @package OCA\Passwords\Helper\Words
 */
class SnakesWordsHelper extends AbstractWordsHelper {

    const SERVICE_URL = 'http://watchout4snakes.com/wo4snakes/Random/RandomPhrase';

    /**
     * @var bool
     */
    protected static $isAvailable = false;

    /**
     * @var SpecialCharacterHelper
     */
    protected $specialCharacters;

    /**
     * @var IClientService
     */
    protected $httpClientService;

    /**
     * SnakesWordsHelper constructor.
     *
     * @param SpecialCharacterHelper $specialCharacters
     * @param IClientService         $httpClientService
     */
    public function __construct(SpecialCharacterHelper $specialCharacters, IClientService $httpClientService) {
        $this->specialCharacters = $specialCharacters;
        $this->httpClientService = $httpClientService;
    }

    /**
     * @param int  $strength
     * @param bool $addNumbers
     * @param bool $addSpecial
     *
     * @return array|null
     */
    public function getWords(int $strength, bool $addNumbers, bool $addSpecial): ?array {
        $optionSets = $this->getServiceOptions($strength);

        $wordSets = [];
        foreach($optionSets as $options) {
            for($i = 0; $i < 24; $i++) {
                $result = trim($this->getHttpRequest($options));
                if(empty($result)) continue;

                $words = explode(' ', $result);

                if($this->isWordsArrayValid($words)) {
                    $wordSets = array_merge($wordSets, $words);
                    continue 2;
                };
            }

            return null;
        }

        return [
            'password' => $this->wordsArrayToPassword($wordSets, $strength, $addNumbers, $addSpecial),
            'words'    => $wordSets
        ];
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    protected function getHttpRequest(array $options = []) {
        $httpClient = $this->httpClientService->newClient();
        $response   = $httpClient->post(self::SERVICE_URL, ['body' => $options]);

        return $response->getBody();
    }

    /**
     * @param int $strength
     *
     * @return array
     */
    protected function getServiceOptions(int $strength): array {
        if($strength === 1) {
            return [
                [
                    'Pos1'   => 'a',
                    'Level1' => 20,
                    'Pos2'   => 'a',
                    'Level2' => 35,
                    'Pos3'   => 'n',
                    'Level3' => 50,
                ]
            ];
        }

        if($strength === 2) {
            return [
                [
                    'Pos1'   => 'a',
                    'Level1' => 20,
                    'Pos2'   => 'n',
                    'Level2' => 35,
                    'Pos3'   => 'a',
                    'Level3' => 50,
                    'Pos4'   => 'n',
                    'Level4' => 50,
                ]
            ];
        }

        if($strength === 3) {
            return [
                [
                    'Pos1'   => 'a',
                    'Level1' => 20,
                    'Pos2'   => 'a',
                    'Level2' => 35,
                    'Pos3'   => 'n',
                    'Level3' => 35,
                ],
                [
                    'Pos1'   => 'a',
                    'Level1' => 35,
                    'Pos2'   => 'a',
                    'Level2' => 50,
                    'Pos3'   => 'n',
                    'Level3' => 50,
                ]
            ];
        }

        return [
            [
                'Pos1'   => 'a',
                'Level1' => 20,
                'Pos2'   => 'a',
                'Level2' => 35,
                'Pos3'   => 'a',
                'Level3' => 45,
                'Pos4'   => 'n',
                'Level4' => 35,
            ],
            [
                'Pos1'   => 'a',
                'Level1' => 35,
                'Pos2'   => 'a',
                'Level2' => 45,
                'Pos3'   => 'a',
                'Level3' => 55,
                'Pos4'   => 'n',
                'Level4' => 50
            ]
        ];
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
     * @inheritdoc
     */
    public function isAvailable(): bool {
        if(static::$isAvailable) return static::$isAvailable;

        try {
            $client   = $this->httpClientService->newClient();
            $response = $client->head(SnakesWordsHelper::SERVICE_URL);

            static::$isAvailable = $response->getStatusCode() === 200;

            return static::$isAvailable;
        } catch(\Exception $e) {
            return false;
        }
    }
}