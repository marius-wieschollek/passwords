<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use \OCA\Test\Passwords\Entities\CreatesEntitiesTrait;
use Exception;
use OCA\Passwords\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class ValidateTagTest
 *
 * @package OCA\Passwords\Services
 * @covers  \OCA\Passwords\Services\ValidationService
 */
class ValidateTagTest extends TestCase {

    use CreatesEntitiesTrait;

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var UserChallengeService
     */
    protected $challengeService;

    /**
     * @throws Exception
     */
    public function testValidateTagInvalidSse() {
        $mock = $this->createTagRevision(['sseType' => 'invalid']);

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7b584c1e', $e->getId());
            $this->assertEquals('Invalid server side encryption type', $e->getMessage());
        }
    }


    /**
     *
     * ValidateTag Tests
     *
     */

    /**
     * @throws Exception
     */
    public function testValidateTagInvalidCse() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => 'invalid'
            ]
        );

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateTagCseKeyButNoCse() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::CSE_ENCRYPTION_NONE,
                'cseKey'  => 'cse-key'
            ]
        );

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateTagNoSseAndCse() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::SSE_ENCRYPTION_NONE,
                'cseType' => EncryptionService::CSE_ENCRYPTION_NONE
            ]
        );

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('f43e7b82', $e->getId());
            $this->assertEquals('No encryption specified', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateTagMissingCseKey() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::SSE_ENCRYPTION_NONE,
                'cseType' => EncryptionService::CSE_ENCRYPTION_V1R1,
                'cseKey'  => ''
            ]
        );
        $this->challengeService->method('hasChallenge')->willReturn(true);

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('fce89df4', $e->getId());
            $this->assertEquals('Client side encryption key missing', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateTagEmptyLabel() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION
            ]
        );

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7c31eb4d', $e->getId());
            $this->assertEquals('Field "label" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateTagEmptyColor() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'   => 'label'
            ]
        );

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('2aff026c', $e->getId());
            $this->assertEquals('Field "color" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateTagSetsSseType() {
        $mock = $this->createTagRevision(
            [
                'sseType' => '',
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'   => 'label',
                'color'   => 'color',
                'edited'  => 1
            ]
        );

        $this->validationService->validateTag($mock);
        $this->assertArrayHasKey('sseType', $mock->getUpdatedFields());
        $this->assertSame(EncryptionService::DEFAULT_SSE_ENCRYPTION, $mock->getSseType());
    }

    /**
     * @throws Exception
     */
    public function testValidateTagSetsCseType() {
        $mock = $this->createTagRevision(
            [
                'cseType' => '',
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'label'   => 'label',
                'color'   => 'color',
                'edited'  => 1
            ]
        );

        $this->validationService->validateTag($mock);
        $this->assertArrayHasKey('cseType', $mock->getUpdatedFields());
        $this->assertSame(EncryptionService::DEFAULT_CSE_ENCRYPTION, $mock->getCseType());
    }

    /**
     * @throws Exception
     */
    public function testValidateTagSetsEditedWhenEmpty() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'   => 'label',
                'color'   => 'color',
                'edited'  => 0
            ]
        );

        $this->validationService->validateTag($mock);
        $this->assertArrayHasKey('edited', $mock->getUpdatedFields());
    }

    /**
     * @throws Exception
     */
    public function testValidateTagSetsEditedWhenInFuture() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'   => 'label',
                'color'   => 'color',
                'edited'  => strtotime('+2 hours')
            ]
        );

        $this->validationService->validateTag($mock);
        $this->assertArrayHasKey('edited', $mock->getUpdatedFields());
    }

    /**
     *
     */
    public function testValidateTagCseUsedButNotAvailable() {
        $mock = $this->createTagRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::CSE_ENCRYPTION_V1R1
            ]
        );

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    /**
     *
     */
    protected function setUp(): void {
        $container = $this->createMock(ContainerInterface::class);

        $this->challengeService = $this->createMock(UserChallengeService::class);
        $container->method('get')->willReturn($this->challengeService);

        $this->validationService = new ValidationService($container);
    }
}