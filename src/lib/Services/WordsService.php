<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Helper\Words\AbstractWordsHelper;
use Throwable;

/**
 * Class WordsService
 *
 * @package OCA\Passwords\Services
 */
class WordsService {

    /**
     * @var LoggingService
     */
    protected LoggingService $logger;

    /**
     * @var AbstractWordsHelper
     */
    protected AbstractWordsHelper $wordsHelper;

    /**
     * @var AbstractSecurityCheckHelper
     */
    protected AbstractSecurityCheckHelper $securityHelper;

    /**
     * FaviconService constructor.
     *
     * @param HelperService  $helperService
     * @param LoggingService $logger
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
     * @param int  $attempts
     *
     * @return array
     * @throws ApiException
     */
    public function getPassword(
        int $strength = 1,
        bool $addNumbers = false,
        bool $addSpecialCharacters = false,
        int $attempts = 0
    ) {
        $strength = $this->validateStrength($strength);

        try {
            $result = $this->wordsHelper->getWords($strength, $addNumbers, $addSpecialCharacters);

            if($result !== null) {
                if($this->isSecure($result['password'], $addNumbers, $addSpecialCharacters, $strength +1 )) {
                    return [$result['password'], $result['words'], $strength];
                } else {
                    $this->logger->warning('Words service delivered low quality result');
                }
            } else {
                $this->logger->warning('Words service delivered no result');
            }
        } catch(Throwable $e) {
            $this->logger->logException($e);
        }

        if($attempts < 6) {
            return $this->getPassword($strength, $addNumbers, $addSpecialCharacters, $attempts + 1);
        }

        $this->logger->error("Words service failed {$attempts} times. Returning error to client.");
        throw new ApiException('Internal Words API Error', 502);
    }

    /**
     * @param string $password
     * @param bool   $addNumbers
     * @param bool   $addSpecialCharacters
     * @param int    $charCount
     *
     * @return bool
     */
    protected function isSecure(string $password, bool $addNumbers, bool $addSpecialCharacters, int $charCount): bool {
        if(strlen($password) < 12) return false;

        if($addNumbers) {
            if(!preg_match_all('/[0-9]/', $password, $numbers) || count($numbers[0]) < $charCount) {
                return false;
            }
        }

        if($addSpecialCharacters) {
            if(!preg_match_all('/\W/u', $password, $specials) || count($specials[0]) < $charCount) {
                return false;
            }
        }

        return $this->securityHelper->isPasswordSecure($password);
    }

    /**
     * @param int $strength
     *
     * @return int
     */
    protected function validateStrength(int $strength): int {
        if($strength < 0) {
            return 1;
        } else if($strength > 4) {
            return 4;
        }

        return $strength;
    }
}