<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Uuid;

use Exception;
use OCA\Passwords\Services\LoggingService;
use Random\Randomizer;

/**
 * Class UuidHelper
 *
 * @package OCA\Passwords\Helper\Uuid
 */
class UuidHelper {

    /**
     * UuidHelper constructor.
     *
     * @param LoggingService $logger
     * @param Randomizer     $randomizer
     */
    public function __construct(protected LoggingService $logger, protected Randomizer $randomizer) {
    }

    /**
     * @return string
     */
    public function generateUuid(): string {
        try {
            return $this->generateUuidV4();
        } catch(Exception $e) {
            $this->logger->error('Could not generate UUIDv4');
            $this->logger->logException($e);

            return $this->generateFallbackUuid();
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function generateUuidV4(): string {
        return implode('-', [
            bin2hex($this->randomizer->getBytes(4)),
            bin2hex($this->randomizer->getBytes(2)),
            bin2hex(chr((ord($this->randomizer->getBytes(1)) & 0x0F) | 0x40)).bin2hex($this->randomizer->getBytes(1)),
            bin2hex(chr((ord($this->randomizer->getBytes(1)) & 0x3F) | 0x80)).bin2hex($this->randomizer->getBytes(1)),
            bin2hex($this->randomizer->getBytes(6))
        ]);
    }

    /**
     * @return string
     */
    protected function generateFallbackUuid(): string {
        $string = uniqid().uniqid().uniqid();

        return substr($string, 0, 8).'-'.
               substr($string, 8, 4).'-'.
               substr($string, 12, 4).'-'.
               substr($string, 16, 4).'-'.
               substr($string, 20, 12);
    }
}