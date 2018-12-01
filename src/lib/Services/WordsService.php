<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Exception\ApiException;

/**
 * Class WordsService
 *
 * @package OCA\Passwords\Services
 */
class WordsService {

    /**
     * @var int
     */
    protected $retries = 0;

    /**
     * @var LoggingService
     */
    protected $logger;

    /**
     * @var \OCA\Passwords\Helper\Words\AbstractWordsHelper
     */
    protected $wordsHelper;

    /**
     * @var \OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper
     */
    protected $securityHelper;

    /**
     * FaviconService constructor.
     *
     * @param HelperService  $helperService
     * @param LoggingService $logger
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(HelperService $helperService, LoggingService $logger) {
        $this->wordsHelper    = $helperService->getWordsHelper();
        $this->securityHelper = $helperService->getSecurityHelper();
        $this->logger         = $logger;
    }

    /**
     * @param int  $strength
     * @param bool $addNumbers
     * @param bool $addSpecialCharacters
     *
     * @return array
     * @throws ApiException
     */
    public function getPassword(
        int $strength = 1,
        bool $addNumbers = false,
        bool $addSpecialCharacters = false
    ) {
        $strength = $this->validateStrength($strength);

        try {
            $this->retries++;
            if($this->retries > 5) throw new Exception('Passwords Service Not Responding');

            $words    = $this->wordsHelper->getWords($strength);
            $password = $this->wordsToPassword($words);

            if(strlen($password) < 12) return $this->getPassword($strength, $addNumbers, $addSpecialCharacters);

            $amount = $strength == 1 ? 2:$strength;
            if($addNumbers) $password = $this->addNumbers($password, $amount);
            if($addSpecialCharacters) $password = $this->addSpecialCharacters($password, $amount);

            if(!$this->securityHelper->isPasswordSecure($password)) {
                return $this->getPassword($strength, $addNumbers, $addSpecialCharacters);
            }

            return [$password, $words, $strength];
        } catch(\Throwable $e) {
            $this->logger->logException($e);

            throw new ApiException('Internal Words API Error'. 502);
        }
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
     * @param        $list
     *
     * @return string
     */
    protected function replaceCharacters(string $word, int $amount, $list): string {
        $rounds       = 0;
        $replacements = 0;
        $reverse      = false;
        while($rounds < $amount && $replacements < $amount) {
            foreach($list as $find => $replace) {
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
     * @param int $strength
     *
     * @return int
     */
    protected function validateStrength(int $strength): int {
        if($strength < 1) {
            return 1;
        } else if($strength > 4) {
            return 4;
        }

        return $strength;
    }
}