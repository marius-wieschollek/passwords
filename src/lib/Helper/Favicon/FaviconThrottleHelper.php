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

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Exception\RateLimitExceededException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\Security\RateLimiting\ILimiter;
use OCP\Security\RateLimiting\IRateLimitExceededException;

/**
 * Class FaviconThrottleHelper
 *
 * Applies the configurable per-user rate limit for favicon requests.
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class FaviconThrottleHelper {

    const string CONFIG_ENABLED = 'service/favicon/throttle/enabled';
    const string CONFIG_LIMIT   = 'service/favicon/throttle/limit';
    const string CONFIG_PERIOD  = 'service/favicon/throttle/period';

    const bool DEFAULT_ENABLED = true;
    const int  DEFAULT_LIMIT   = 15;
    const int  DEFAULT_PERIOD  = 15;

    /**
     * Same identifier the Nextcloud rate limiting middleware used
     * for the former #[UserRateLimit] attribute on ServiceApiController::getFavicon
     */
    const string LIMITER_IDENTIFIER = 'OCA\Passwords\Controller\Api\ServiceApiController::getFavicon';

    /**
     * FaviconThrottleHelper constructor.
     *
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     * @param ILimiter             $limiter
     */
    public function __construct(
        protected ConfigurationService $config,
        protected EnvironmentService   $environment,
        protected ILimiter             $limiter
    ) {
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool {
        return $this->config->getAppValue(self::CONFIG_ENABLED, self::DEFAULT_ENABLED ? '1':'0') !== '0';
    }

    /**
     * Maximum number of favicon requests a user may make within one period
     *
     * @return int
     */
    public function getLimit(): int {
        return max(1, intval($this->config->getAppValue(self::CONFIG_LIMIT, (string) self::DEFAULT_LIMIT)));
    }

    /**
     * Length of the period in seconds
     *
     * @return int
     */
    public function getPeriod(): int {
        return max(1, intval($this->config->getAppValue(self::CONFIG_PERIOD, (string) self::DEFAULT_PERIOD)));
    }

    /**
     * Register a favicon request for the current user and abort the request
     * with a 429 response if the configured limit has been exceeded.
     *
     * The limit is a sliding window, so after a full period every request
     * counted so far has expired. That makes the period a safe Retry-After.
     *
     * @throws RateLimitExceededException
     */
    public function registerRequest(): void {
        if(!$this->isEnabled()) return;

        $user = $this->environment->getUser();
        if($user === null) return;

        try {
            $this->limiter->registerUserRequest(self::LIMITER_IDENTIFIER, $this->getLimit(), $this->getPeriod(), $user);
        } catch(IRateLimitExceededException $e) {
            throw new RateLimitExceededException($this->getPeriod(), $e);
        }
    }
}
