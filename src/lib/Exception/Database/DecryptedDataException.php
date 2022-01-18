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

namespace OCA\Passwords\Exception\Database;

use Exception;
use OCA\Passwords\Db\AbstractRevision;

class DecryptedDataException extends Exception {

    /**
     * @param AbstractRevision $revision
     * @param int              $code
     * @param \Throwable|null  $previous
     */
    public function __construct(AbstractRevision $revision, int $code = 0, ?\Throwable $previous = null) {
        parent::__construct("Can not save decrypted revision {$revision->getUuid()}", $code, $previous);
    }
}