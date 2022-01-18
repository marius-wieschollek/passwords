<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\Words;

use OCA\Passwords\Services\LoggingService;

class AutoWordsHelper extends AbstractWordsHelper {

    protected LeipzigCorporaHelper   $leipzigCorporaHelper;
    protected LocalWordsHelper       $localWordsHelper;
    protected RandomCharactersHelper $randomCharactersHelper;
    protected LoggingService         $logger;

    /**
     * @param LeipzigCorporaHelper   $leipzigCorporaHelper
     * @param LocalWordsHelper       $localWordsHelper
     * @param RandomCharactersHelper $randomCharactersHelper
     * @param LoggingService         $logger
     */
    public function __construct(
        LeipzigCorporaHelper $leipzigCorporaHelper,
        LocalWordsHelper $localWordsHelper,
        RandomCharactersHelper $randomCharactersHelper,
        LoggingService $logger,
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