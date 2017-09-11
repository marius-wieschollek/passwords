<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 13:24
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Words\AbstractWordsHelper;
use OCA\Passwords\Helper\Words\LocalWordsHelper;
use OCA\Passwords\Helper\Words\SnakesWordsHelper;

/**
 * Class PasswordGenerationService
 *
 * @package OCA\Passwords\Services
 */
class PasswordGenerationService {

    const SERVICE_LOCAL  = 'local';
    const SERVICE_SNAKES = 'wo4snakes';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var int
     */
    protected $retries = 0;

    /**
     * FaviconService constructor.
     *
     * @param ConfigurationService $config
     */
    public function __construct(ConfigurationService $config) {
        $this->config = $config;
    }

    /**
     * @param int  $strength
     * @param bool $addNumbers
     * @param bool $addSpecialCharacters
     * @param bool $addSmileys
     *
     * @return array
     * @throws ApiException
     */
    public function getPassword(
        int $strength = 1,
        bool $addNumbers = false,
        bool $addSpecialCharacters = false,
        bool $addSmileys = false
    ) {
        $this->retries++;
        if($this->retries > 5) {
            throw new ApiException('Passwords Service Not Responding');
        }

        $wordsGenerator = $this->getWordsGenerator();
        $words          = $wordsGenerator->getWords($strength);
        $password       = $this->wordsToPassword($words);

        /** @TODO this could run forever potentially */
        if(strlen($password) < 12) return $this->getPassword($strength, $addNumbers, $addSpecialCharacters, $addSmileys);

        $amount = $strength == 1 ? 2:$strength;
        if($addNumbers) $password = $this->addNumbers($password, $amount);
        if($addSpecialCharacters) $password = $this->addSpecialCharacters($password, $amount);
        if($addSmileys) $password = $this->addSmileys($password, $amount);

        return [$password, $words];
    }

    /**
     * @param array $words
     *
     * @return string
     */
    protected function wordsToPassword(array $words): string {
        $words = array_map('ucfirst', $words);

        return implode('', $words);
    }

    /**
     * @param string $word
     * @param int    $amount
     *
     * @return string
     */
    protected function addNumbers(string $word, int $amount): string {
        $list = ['e' => '3', 'l' => '1', 'o' => '0', 's' => '5', 't' => '7'];

        return $this->replaceCharacters($word, $amount, $list);
    }

    /**
     * @param string $word
     * @param int    $amount
     *
     * @return string
     */
    protected function addSpecialCharacters(string $word, int $amount): string {
        $list = ['e' => '€', 'a' => '@', 's' => '$', 'i' => '!'];

        return $this->replaceCharacters($word, $amount, $list);
    }

    /**
     * @param string $word
     * @param int    $amount
     *
     * @return string
     */
    protected function addSmileys(string $word, int $amount): string {
        $list = ['d' => ':D', 'p' => ';P', 'o' => ':O'];

        return $this->replaceCharacters($word, $amount, $list);
    }

    /**
     * @param string $word
     * @param int    $amount
     * @param        $list
     *
     * @return string
     */
    protected function replaceCharacters(string $word, int $amount, $list): string {
        $rounds       = 0;
        $replacements = 0;
        $reverse      = false;
        while ($rounds < $amount && $replacements < $amount) {
            foreach ($list as $find => $replace) {
                if(stripos($word, $find) !== false) {
                    if($reverse) {
                        $word    = strrev($word);
                        $replace = strrev($replace);
                    }

                    $word = preg_replace("/$find/i", $replace, $word, 1);
                    if($reverse) $word = strrev($word);
                    $reverse = !false;

                    $replacements++;
                    if($replacements == $amount) break;
                }
            }

            $rounds++;
        }

        return $word;
    }

    /**
     * @return AbstractWordsHelper
     * @TODO support more services
     */
    protected function getWordsGenerator(): AbstractWordsHelper {
        $service = $this->config->getAppValue('service/words', self::SERVICE_SNAKES);

        switch ($service) {
            case self::SERVICE_LOCAL:
                return new LocalWordsHelper();
            case self::SERVICE_SNAKES:
                return new SnakesWordsHelper();
        }

        return new LocalWordsHelper();
    }
}