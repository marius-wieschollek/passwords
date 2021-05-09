<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\Object\FolderService;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Class ValidatePasswordTest
 *
 * @package OCA\Passwords\Services
 * @covers  \OCA\Passwords\Services\ValidationService
 */
class ValidatePasswordTest extends TestCase {

    /**
     * @var ValidationService
     */
    protected $validationService;
    /**
     * @var UserChallengeService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $challengeService;

    /**
     *
     */
    protected function setUp(): void {
        $container = $this->createMock('\OCP\AppFramework\IAppContainer');

        $this->challengeService = $this->createMock(UserChallengeService::class);
        $container->method('get')->willReturn($this->challengeService);

        $this->validationService = new ValidationService($container);
    }

    /**
     * Validate Password Tests
     */

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordInvalidSse() {
        $mock = $this->getPasswordMock();
        $mock->method('getSseType')->willReturn('invalid');

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordInvalidCse() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn('invalid');

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordCseKeyBotNoCse() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::CSE_ENCRYPTION_NONE);
        $mock->method('getCseKey')->willReturn('cse-key');

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordNoSseAndCse() {
        $mock = $this->getPasswordMock();
        $mock->method('getSseType')->willReturn(EncryptionService::SSE_ENCRYPTION_NONE);
        $mock->method('getCseType')->willReturn(EncryptionService::CSE_ENCRYPTION_NONE);

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordMissingCseKey() {
        $mock = $this->getPasswordMock();
        $this->challengeService->method('hasChallenge')->willReturn(true);
        $mock->method('getSseType')->willReturn(EncryptionService::SSE_ENCRYPTION_NONE);
        $mock->method('getCseType')->willReturn(EncryptionService::CSE_ENCRYPTION_V1R1);
        $mock->method('getCseKey')->willReturn('');

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals('Client side encryption key missing', $e->getMessage());
            $this->assertEquals('fce89df4', $e->getId());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordEmptyLabel() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordEmptyPassword() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('2cf30fe7', $e->getId());
            $this->assertEquals('Field "password" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordEmptyHash() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getPassword')->willReturn('password');

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordInvalidHash() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getPassword')->willReturn('password');
        $mock->method('getHash')->willReturn('hash');

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected invalid hash exception");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordSetsSseType() {
        $mock = $this->getPasswordMock();

        $mock->expects($this->any())
             ->method('getSseType')
             ->will($this->onConsecutiveCalls('', EncryptionService::DEFAULT_SSE_ENCRYPTION));

        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getPassword')->willReturn('password');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setSseType')->with(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $this->validationService->validatePassword($mock);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordSetsCseType() {
        $mock = $this->getPasswordMock();

        $mock->expects($this->any())
             ->method('getCseType')
             ->will($this->onConsecutiveCalls('', EncryptionService::DEFAULT_CSE_ENCRYPTION, EncryptionService::DEFAULT_CSE_ENCRYPTION, EncryptionService::DEFAULT_CSE_ENCRYPTION, EncryptionService::DEFAULT_CSE_ENCRYPTION));

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getPassword')->willReturn('password');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setCseType')->with(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $this->validationService->validatePassword($mock);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordCorrectsInvalidFolderUuid() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getPassword')->willReturn('password');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn('1-2-3');
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setFolder')->with(FolderService::BASE_FOLDER_UUID);
        $this->validationService->validatePassword($mock);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordSetsEditedWhenEmpty() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getPassword')->willReturn('password');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(0);

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validatePassword($mock);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidatePasswordSetsEditedWhenInFuture() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getPassword')->willReturn('password');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(strtotime('+121 minutes'));

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validatePassword($mock);
    }

    /**
     *
     */
    public function testValidateTagCseUsedButNotAvailable() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::CSE_ENCRYPTION_V1R1);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    /**
     * @return PasswordRevision
     */
    protected function getPasswordMock() {
        $mock = $this
            ->getMockBuilder('\OCA\Passwords\Db\PasswordRevision')
            ->setMethods([
                             'getSseType',
                             'setSseType',
                             'getHidden',
                             'getCseType',
                             'setCseType',
                             'getCseKey',
                             'getLabel',
                             'getPassword',
                             'getHash',
                             'getFolder',
                             'setFolder',
                             'getStatus',
                             'getEdited',
                             'setEdited'
                         ])
            ->getMock();

        $mock->method('getHidden')->willReturn(false);

        return $mock;
    }
}