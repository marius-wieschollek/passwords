<?php
/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Exception;

use OCP\AppFramework\Http;
use Throwable;

/**
 * Class RateLimitExceededException
 *
 * Thrown when a client exceeds one of the app's own rate limits.
 * Carries the number of seconds after which the client may try again,
 * which the api middleware sends as the Retry-After header.
 *
 * @package OCA\Passwords\Exception
 */
class RateLimitExceededException extends ApiException {

    /**
     * RateLimitExceededException constructor.
     *
     * @param int            $retryAfter Seconds until the limit is guaranteed to have cleared
     * @param Throwable|null $previous
     */
    public function __construct(private int $retryAfter, ?Throwable $previous = null) {
        parent::__construct('Too many requests', Http::STATUS_TOO_MANY_REQUESTS, $previous);
    }

    /**
     * @return int
     */
    public function getRetryAfter(): int {
        return $this->retryAfter;
    }
}
