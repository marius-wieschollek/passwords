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

use OCA\Passwords\Services\LoggingService;

class AutoWordsProvider extends AbstractWordsProvider {

    protected LeipzigCorporaProvider   $leipzigCorporaHelper;
    protected LocalWordsProvider       $localWordsHelper;
    protected RandomCharactersProvider $randomCharactersHelper;
    protected LoggingService           $logger;

    /**
     * @param LeipzigCorporaProvider   $leipzigCorporaHelper
     * @param LocalWordsProvider       $localWordsHelper
     * @param RandomCharactersProvider $randomCharactersHelper
     * @param LoggingService           $logger
     */
    public function __construct(
        LeipzigCorporaProvider   $leipzigCorporaHelper,
        LocalWordsProvider       $localWordsHelper,
        RandomCharactersProvider $randomCharactersHelper,
        LoggingService           $logger,
    ) {
        $this->leipzigCorporaHelper   = $leipzigCorporaHelper;
        $this->localWordsHelper       = $localWordsHelper;
        $this->randomCharactersHelper = $randomCharactersHelper;
        $this->logger = $logger;
    }

    public function getWords(int $strength, bool $addNumbers, bool $addSpecial): ?array {
        try {
            $result = $this->leipzigCorporaHelper->getWords($strength, $addNumbers, $addSpecial);
            if($result !== null) {
                return $result;
            }
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        try {
            if($this->localWordsHelper->isAvailable()) {
                $result = $this->localWordsHelper->getWords($strength, $addNumbers, $addSpecial);
                if($result !== null) {
                    return $result;
                }
            }
        } catch(\Throwable $e) {
            $this->logger->logException($e);
        }

        return $this->randomCharactersHelper->getWords($strength, $addNumbers, $addSpecial);
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool {
        return $this->randomCharactersHelper->isAvailable() ||
               $this->localWordsHelper->isAvailable() ||
               $this->leipzigCorporaHelper->isAvailable();
    }
}