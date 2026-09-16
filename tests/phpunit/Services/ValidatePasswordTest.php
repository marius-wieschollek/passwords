<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Test\Passwords\Entities\CreatesEntitiesTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class ValidatePasswordTest
 *
 * @package OCA\Passwords\Services
 * @covers  \OCA\Passwords\Services\ValidationService
 */
class ValidatePasswordTest extends TestCase {

    use CreatesEntitiesTrait;

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     * @var UserChallengeService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $challengeService;

    /**
     * @var PasswordService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $passwordService;

    /**
     * @var UserSettingsHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $userSettingsHelper;

    /**
     * @throws Exception
     */
    public function testValidatePasswordInvalidSse() {
        $mock = $this->createPasswordRevision(
            [
                'sseType' => 'invalid'
            ]
        );

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7b584c1e', $e->getId());
            $this->assertEquals('Invalid server side encryption type', $e->getMessage());
        }
    }

    /**
     * Validate Password Tests
     */

    /**
     * @throws Exception
     */
    public function testValidatePasswordInvalidCse() {
        $mock = $this->createPasswordRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => 'invalid'
            ]
        );

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordCseKeyButNoCse() {
        $mock = $this->createPasswordRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::CSE_ENCRYPTION_NONE,
                'cseKey'  => 'cse-key'
            ]
        );

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordNoSseAndCse() {
        $mock = $this->createPasswordRevision(
            [
                'sseType' => EncryptionService::SSE_ENCRYPTION_NONE,
                'cseType' => EncryptionService::CSE_ENCRYPTION_NONE
            ]
        );

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordMissingCseKey() {
        $mock = $this->createPasswordRevision(
            [
                'sseType' => EncryptionService::SSE_ENCRYPTION_NONE,
                'cseType' => EncryptionService::CSE_ENCRYPTION_V1R1,
                'cseKey'  => ''
            ]
        );
        $this->challengeService->method('hasChallenge')->willReturn(true);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals('Client side encryption key missing', $e->getMessage());
            $this->assertEquals('fce89df4', $e->getId());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordEmptyLabel() {
        $mock = $this->createPasswordRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION
            ]
        );

        try {
            $this->validationService->validatePassword($mock);
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
    public function testValidatePasswordEmptyPassword() {
        $mock = $this->createPasswordRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'   => 'label'
            ]
        );

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('2cf30fe7', $e->getId());
            $this->assertEquals('Field "password" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordInvalidEmptyHash() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'model'    => 'model',
                'id'       => 'id',
                'hash'     => '',
            ]
        );


        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(40);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordInvalidHash() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => 'hash',
                'model'    => 'model',
                'id'       => 'id',
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(40);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected invalid hash exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordInvalidHashWithCustomLength() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => 'hash',
                'model'    => 'model',
                'id'       => 'id',
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(30);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected invalid hash exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateNewPasswordInvalidEmptyHash() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => '',
                'id'       => null,
            ]
        );

        $this->userSettingsHelper->method('get')->willReturn(40);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateNewPasswordInvalidHash() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => 'hash',
                'model'    => 'model',
                'id'       => null,
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(40);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected invalid hash exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidateNewPasswordInvalidHashWithCustomLength() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => 'hash',
                'model'    => 'model',
                'id'       => null,
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(30);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected invalid hash exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordInvalidHashWithSharedPassword() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => 'hash',
                'model'    => 'model',
                'id'       => 'id',
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => false,
                'shareId'  => 'share-id'
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected invalid hash exception");
        } catch (ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordValidEmptyHash() {
        $mock = $this->createPasswordRevisionMock(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => '',
                'model'    => 'model',
                'id'       => 'id',
                'folder'   => FolderService::BASE_FOLDER_UUID
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(0);

        $this->validationService->validatePassword($mock);
        $this->assertContains('getHash', $mock->getUnitCalls());
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordValidFullHash() {
        $mock = $this->createPasswordRevisionMock(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => sha1('hash'),
                'model'    => 'model',
                'id'       => 'id',
                'folder'   => FolderService::BASE_FOLDER_UUID
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(40);

        $this->validationService->validatePassword($mock);
        $this->assertContains('getHash', $mock->getUnitCalls());
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordValidHashWithCustomLength() {
        $mock = $this->createPasswordRevisionMock(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => substr(sha1('hash'), 0, 30),
                'model'    => 'model',
                'id'       => 'id',
                'folder'   => FolderService::BASE_FOLDER_UUID
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(30);

        $this->validationService->validatePassword($mock);
        $this->assertContains('getHash', $mock->getUnitCalls());
    }

    /**
     * @throws Exception
     */
    public function testValidateNewPasswordValidEmptyHash() {
        $mock = $this->createPasswordRevisionMock(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => '',
                'id'       => null,
                'folder'   => FolderService::BASE_FOLDER_UUID
            ]
        );

        $this->userSettingsHelper->method('get')->willReturn(0);

        $this->validationService->validatePassword($mock);
        $this->assertContains('getHash', $mock->getUnitCalls());
    }

    /**
     * @throws Exception
     */
    public function testValidateNewPasswordValidFullHash() {
        $mock = $this->createPasswordRevisionMock(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => sha1('hash'),
                'model'    => 'model',
                'id'       => null,
                'folder'   => FolderService::BASE_FOLDER_UUID
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(40);

        $this->validationService->validatePassword($mock);
        $this->assertContains('getHash', $mock->getUnitCalls());
    }

    /**
     * @throws Exception
     */
    public function testValidateNewPasswordValidHashWithCustomLength() {
        $mock = $this->createPasswordRevisionMock(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => substr(sha1('hash'), 0, 30),
                'model'    => 'model',
                'id'       => null,
                'folder'   => FolderService::BASE_FOLDER_UUID
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => true,
                'shareId'  => ''
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(30);

        $this->validationService->validatePassword($mock);
        $this->assertContains('getHash', $mock->getUnitCalls());
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordValidHashWithSharedPassword() {
        $mock = $this->createPasswordRevisionMock(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => substr(sha1('hash'), 0, 30),
                'model'    => 'model',
                'id'       => 'id',
                'folder'   => FolderService::BASE_FOLDER_UUID
            ]
        );

        $modelMock = $this->createPasswordModel(
            [
                'editable' => false,
                'shareId'  => 'share-id'
            ]
        );
        $this->passwordService->method('findByUuid')->willReturn($modelMock);

        $this->userSettingsHelper->method('get')->willReturn(30);

        $this->validationService->validatePassword($mock);
        $this->assertContains('getHash', $mock->getUnitCalls());
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordSetsSseType() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => '',
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => sha1('hash'),
                'model'     => 'model',
                'id'       => null,
                'folder'   => FolderService::BASE_FOLDER_UUID,
                'status'   => 2,
                'edited'   => 1
            ]
        );

        $this->validationService->validatePassword($mock);
        $this->assertArrayHasKey('sseType', $mock->getUpdatedFields());
        $this->assertSame(EncryptionService::DEFAULT_SSE_ENCRYPTION, $mock->getSseType());
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordSetsCseType() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => '',
                'label'    => 'label',
                'password' => 'password',
                'hash'     => sha1('hash'),
                'folder'   => FolderService::BASE_FOLDER_UUID,
                'status'   => 2,
                'edited'   => 1
            ]
        );

        $this->validationService->validatePassword($mock);
        $this->assertArrayHasKey('cseType', $mock->getUpdatedFields());
        $this->assertSame(EncryptionService::DEFAULT_CSE_ENCRYPTION, $mock->getCseType());
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordCorrectsInvalidFolderUuid() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => sha1('hash'),
                'folder'   => '1-2-3',
                'status'   => 2,
                'edited'   => 1
            ]
        );

        $this->validationService->validatePassword($mock);
        $this->assertArrayHasKey('folder', $mock->getUpdatedFields());
        $this->assertSame(FolderService::BASE_FOLDER_UUID, $mock->getFolder());
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordSetsEditedWhenEmpty() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => sha1('hash'),
                'folder'   => FolderService::BASE_FOLDER_UUID,
                'status'   => 2,
                'edited'   => 0
            ]
        );

        $this->validationService->validatePassword($mock);
        $this->assertArrayHasKey('edited', $mock->getUpdatedFields());
    }

    /**
     * @throws Exception
     */
    public function testValidatePasswordSetsEditedWhenInFuture() {
        $mock = $this->createPasswordRevision(
            [
                'sseType'  => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType'  => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label'    => 'label',
                'password' => 'password',
                'hash'     => sha1('hash'),
                'folder'   => FolderService::BASE_FOLDER_UUID,
                'status'   => 2,
                'edited'   => strtotime('+121 minutes')
            ]
        );

        $this->validationService->validatePassword($mock);
        $this->assertArrayHasKey('edited', $mock->getUpdatedFields());
    }

    /**
     *
     */
    public function testValidateTagCseUsedButNotAvailable() {
        $mock = $this->createPasswordRevision(
            [

                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::CSE_ENCRYPTION_V1R1,
            ]
        );

        try {
            $this->validationService->validatePassword($mock);
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
        $this->passwordService = $this->createMock(PasswordService::class);
        $this->userSettingsHelper = $this->createMock(UserSettingsHelper::class);
        $container->method('get')->willReturnMap(
            [
                [UserChallengeService::class, $this->challengeService],
                [PasswordService::class, $this->passwordService],
                [UserSettingsHelper::class, $this->userSettingsHelper]
            ]
        );

        $this->validationService = new ValidationService($container);
    }
}