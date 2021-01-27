<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Exception\ApiException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Class ValidateTagTest
 *
 * @package OCA\Passwords\Services
 * @covers  \OCA\Passwords\Services\ValidationService
 */
class ValidateTagTest extends TestCase {

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     *
     */
    protected function setUp(): void {
        $container               = $this->createMock('\OCP\AppFramework\IAppContainer');

        $this->challengeService = $this->createMock(UserChallengeService::class);
        $container->method('get')->willReturn($this->challengeService);

        $this->validationService = new ValidationService($container);
    }


    /**
     *
     * ValidateTag Tests
     *
     */
    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagInvalidSse() {
        $mock = $this->getTagMock();
        $mock->method('getSseType')->willReturn('invalid');

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7b584c1e', $e->getId());
            $this->assertEquals('Invalid server side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagInvalidCse() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn('invalid');

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagCseKeyButNoCse() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::CSE_ENCRYPTION_NONE);
        $mock->method('getCseKey')->willReturn('cse-key');

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagNoSseAndCse() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::SSE_ENCRYPTION_NONE);
        $mock->method('getCseType')->willReturn(EncryptionService::CSE_ENCRYPTION_NONE);

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('f43e7b82', $e->getId());
            $this->assertEquals('No encryption specified', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagMissingCseKey() {
        $mock = $this->getTagMock();
        $this->challengeService->method('hasChallenge')->willReturn(true);
        $mock->method('getSseType')->willReturn(EncryptionService::SSE_ENCRYPTION_NONE);
        $mock->method('getCseType')->willReturn(EncryptionService::CSE_ENCRYPTION_V1R1);
        $mock->method('getCseKey')->willReturn('');

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('fce89df4', $e->getId());
            $this->assertEquals('Client side encryption key missing', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagEmptyLabel() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7c31eb4d', $e->getId());
            $this->assertEquals('Field "label" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagEmptyColor() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('2aff026c', $e->getId());
            $this->assertEquals('Field "color" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagSetsSseType() {
        $mock = $this->getTagMock();

        $mock->expects($this->any())
             ->method('getSseType')
             ->will($this->onConsecutiveCalls('', EncryptionService::DEFAULT_SSE_ENCRYPTION));

        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getColor')->willReturn('color');
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setSseType')->with(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $this->validationService->validateTag($mock);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagSetsCseType() {
        $mock = $this->getTagMock();

        $mock->expects($this->any())
             ->method('getCseType')
             ->will($this->onConsecutiveCalls('', EncryptionService::DEFAULT_CSE_ENCRYPTION, EncryptionService::DEFAULT_CSE_ENCRYPTION, EncryptionService::DEFAULT_CSE_ENCRYPTION, EncryptionService::DEFAULT_CSE_ENCRYPTION));

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getColor')->willReturn('color');
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setCseType')->with(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $this->validationService->validateTag($mock);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagSetsEditedWhenEmpty() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getColor')->willReturn('color');
        $mock->method('getEdited')->willReturn(0);

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validateTag($mock);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateTagSetsEditedWhenInFuture() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getColor')->willReturn('color');
        $mock->method('getEdited')->willReturn(strtotime('+2 hours'));

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validateTag($mock);
    }

    /**
     *
     */
    public function testValidateTagCseUsedButNotAvailable() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::CSE_ENCRYPTION_V1R1);

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    /**
     * @return TagRevision
     */
    protected function getTagMock() {
        $mock = $this
            ->getMockBuilder('\OCA\Passwords\Db\TagRevision')
            ->setMethods(['getSseType', 'setSseType', 'getCseType', 'setCseType', 'getCseKey', 'getHidden', 'getLabel', 'getColor', 'getEdited', 'setEdited'])
            ->getMock();

        $mock->method('getHidden')->willReturn(false);

        return $mock;
    }
}