<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Test\Passwords\Entities\CreatesEntitiesTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Class ValidateFolderTest
 *
 * @package OCA\Passwords\Services
 */
class ValidateFolderTest extends TestCase {

    use CreatesEntitiesTrait;

    /**
     * @var ValidationService
     */
    protected $validationService;
    /**
     * @var UserChallengeService|MockObject
     */
    protected $challengeService;

    /**
     *
     */
    protected function setUp(): void {
        $container               = $this->createMock(ContainerInterface::class);

        $this->challengeService = $this->createMock(UserChallengeService::class);
        $container->method('get')->willReturn($this->challengeService);

        $this->validationService = new ValidationService($container);
    }

    /**
     *
     * ValidateFolder Tests
     *
     */
    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateFolderInvalidSse() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => 'invalid',
            ]
        );

        try {
            $this->validationService->validateFolder($mock);
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
    public function testValidateFolderInvalidCse() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => 'invalid',
            ]
        );

        try {
            $this->validationService->validateFolder($mock);
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
    public function testValidateFolderCseKeyBotNoCse() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::CSE_ENCRYPTION_NONE,
                'cseKey' => 'cse-key',
            ]
        );

        try {
            $this->validationService->validateFolder($mock);
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
    public function testValidateFolderNoSseAndCse() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::SSE_ENCRYPTION_NONE,
                'cseType' => EncryptionService::SSE_ENCRYPTION_NONE
            ]
        );

        try {
            $this->validationService->validateFolder($mock);
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
    public function testValidateFolderMissingCseKey() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::SSE_ENCRYPTION_NONE,
                'cseType' => EncryptionService::CSE_ENCRYPTION_V1R1,
                'cseKey' => '',
            ]
        );
        $this->challengeService->method('hasChallenge')->willReturn(true);


        try {
            $this->validationService->validateFolder($mock);
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
    public function testValidateFolderEmptyLabel() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
            ]
        );

        try {
            $this->validationService->validateFolder($mock);
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
    public function testValidateFolderSetsSseType() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => '',
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label' => 'label',
                'parent' => FolderService::BASE_FOLDER_UUID,
                'edited' => 1
            ]
        );

        $this->validationService->validateFolder($mock);
        $this->assertArrayHasKey('sseType', $mock->getUpdatedFields());
        $this->assertSame(EncryptionService::DEFAULT_SSE_ENCRYPTION, $mock->getSseType());
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateFolderSetsCseType() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => '',
                'label' => 'label',
                'parent' => FolderService::BASE_FOLDER_UUID,
                'edited' => 1
            ]
        );

        $this->validationService->validateFolder($mock);
        $this->assertArrayHasKey('cseType', $mock->getUpdatedFields());
        $this->assertSame(EncryptionService::DEFAULT_CSE_ENCRYPTION, $mock->getCseType());
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateFolderCorrectsInvalidFolderUuid() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label' => 'label',
                'parent' => '1-2-3',
                'edited' => 1
            ]
        );

        $this->validationService->validateFolder($mock);
        $this->assertArrayHasKey('parent', $mock->getUpdatedFields());
        $this->assertSame(FolderService::BASE_FOLDER_UUID, $mock->getParent());
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateFolderCorrectsFolderParentLoop() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label' => 'label',
                'parent' => '11111111-1111-1111-1111-111111111111',
                'model' => '11111111-1111-1111-1111-111111111111',
                'edited' => 1
            ]
        );

        $this->validationService->validateFolder($mock);
        $this->assertArrayHasKey('parent', $mock->getUpdatedFields());
        $this->assertSame(FolderService::BASE_FOLDER_UUID, $mock->getParent());
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateFolderSetsEditedWhenEmpty() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label' => 'label',
                'parent' => FolderService::BASE_FOLDER_UUID,
                'edited' => 0
            ]
        );

        $this->validationService->validateFolder($mock);
        $this->assertArrayHasKey('edited', $mock->getUpdatedFields());
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateFolderSetsEditedWhenInFuture() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::DEFAULT_CSE_ENCRYPTION,
                'label' => 'label',
                'parent' => FolderService::BASE_FOLDER_UUID,
                'edited' => strtotime('+2 hours')
            ]
        );

        $this->validationService->validateFolder($mock);
        $this->assertArrayHasKey('edited', $mock->getUpdatedFields());
    }

    /**
     *
     */
    public function testValidateTagCseUsedButNotAvailable() {
        $mock = $this->createFolderRevision(
            [
                'sseType' => EncryptionService::DEFAULT_SSE_ENCRYPTION,
                'cseType' => EncryptionService::CSE_ENCRYPTION_V1R1,
            ]
        );

        try {
            $this->validationService->validateFolder($mock);
            $this->fail("Expected exception");
        } catch(ApiException $e) {
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals(400, $e->getHttpCode());
        }
    }
}