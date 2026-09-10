<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use Exception;
use OCA\Passwords\Exception\RateLimitExceededException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\IUser;
use OCP\Security\RateLimiting\ILimiter;
use OCP\Security\RateLimiting\IRateLimitExceededException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class FaviconThrottleHelperTest
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class FaviconThrottleHelperTest extends TestCase {

    /**
     * @var MockObject|ConfigurationService
     */
    protected $config;

    /**
     * @var MockObject|EnvironmentService
     */
    protected $environment;

    /**
     * @var MockObject|ILimiter
     */
    protected $limiter;

    /**
     * @var FaviconThrottleHelper
     */
    protected $helper;

    /**
     *
     */
    protected function setUp(): void {
        $this->config      = $this->createMock(ConfigurationService::class);
        $this->environment = $this->createMock(EnvironmentService::class);
        $this->limiter     = $this->createMock(ILimiter::class);
        $this->helper      = new FaviconThrottleHelper($this->config, $this->environment, $this->limiter);
    }

    /**
     * @param array $values
     */
    protected function mockConfig(array $values): void {
        $this->config->method('getAppValue')->willReturnCallback(
            function(string $key, $default = null) use ($values) {
                return $values[ $key ] ?? $default;
            }
        );
    }

    /**
     * Throttling is enabled with the former hardcoded values by default
     */
    public function testDefaults() {
        $this->mockConfig([]);

        $this->assertTrue($this->helper->isEnabled());
        $this->assertEquals(15, $this->helper->getLimit());
        $this->assertEquals(15, $this->helper->getPeriod());
    }

    /**
     * Configured values are used and never drop below one
     */
    public function testConfiguredValues() {
        $this->mockConfig(
            [
                FaviconThrottleHelper::CONFIG_LIMIT  => '120',
                FaviconThrottleHelper::CONFIG_PERIOD => '0'
            ]
        );

        $this->assertEquals(120, $this->helper->getLimit());
        $this->assertEquals(1, $this->helper->getPeriod());
    }

    /**
     * The limiter is called with the configured values for the current user
     */
    public function testRegisterRequestUsesConfiguredLimits() {
        $user = $this->createMock(IUser::class);
        $this->environment->method('getUser')->willReturn($user);
        $this->mockConfig(
            [
                FaviconThrottleHelper::CONFIG_LIMIT  => '30',
                FaviconThrottleHelper::CONFIG_PERIOD => '60'
            ]
        );

        $this->limiter->expects($this->once())
                      ->method('registerUserRequest')
                      ->with(FaviconThrottleHelper::LIMITER_IDENTIFIER, 30, 60, $user);

        $this->helper->registerRequest();
    }

    /**
     * No limiter call is made when throttling is disabled
     */
    public function testRegisterRequestSkipsWhenDisabled() {
        $this->environment->method('getUser')->willReturn($this->createMock(IUser::class));
        $this->mockConfig([FaviconThrottleHelper::CONFIG_ENABLED => '0']);

        $this->limiter->expects($this->never())->method('registerUserRequest');

        $this->helper->registerRequest();
    }

    /**
     * No limiter call is made when there is no user
     */
    public function testRegisterRequestSkipsWithoutUser() {
        $this->environment->method('getUser')->willReturn(null);
        $this->mockConfig([]);

        $this->limiter->expects($this->never())->method('registerUserRequest');

        $this->helper->registerRequest();
    }

    /**
     * An exceeded limit becomes a 429 that tells the client to retry after one period
     */
    public function testRegisterRequestThrowsRateLimitExceededException() {
        $this->environment->method('getUser')->willReturn($this->createMock(IUser::class));
        $this->mockConfig([FaviconThrottleHelper::CONFIG_PERIOD => '45']);

        $exception = new class('limit') extends Exception implements IRateLimitExceededException {
        };
        $this->limiter->method('registerUserRequest')->willThrowException($exception);

        try {
            $this->helper->registerRequest();
            $this->fail('Expected RateLimitExceededException');
        } catch(RateLimitExceededException $e) {
            $this->assertEquals(429, $e->getHttpCode());
            $this->assertEquals(45, $e->getRetryAfter());
            $this->assertSame($exception, $e->getPrevious());
        }
    }
}
