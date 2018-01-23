<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 14.01.18
 * Time: 19:26
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\Object\FolderService;
use PHPUnit\Framework\TestCase;

class ValidationServiceTest extends TestCase {

    /**
     * @var \OCA\Passwords\Services\ValidationService
     */
    protected $validationService;

    protected function setUp() {
        $helperService           = $this->createMock('\OCA\Passwords\Services\HelperService');
        $container           = $this->createMock('\OCP\AppFramework\IAppContainer');
        $this->validationService = new \OCA\Passwords\Services\ValidationService($helperService, $container);
    }




    /**
     * ValidatePasswordTests
     */

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordInvalidSse() {
        $mock = $this->getPasswordMock();

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7b584c1e', $e->getId());
            $this->assertEquals('Invalid server side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordInvalidCse() {
        $mock = $this->getPasswordMock();
        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordEmptyLabel() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7c31eb4d', $e->getId());
            $this->assertEquals('Field "label" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordEmptyHash() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordInvalidHash() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getHash')->willReturn('hash');

        try {
            $this->validationService->validatePassword($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('5b9e3440', $e->getId());
            $this->assertEquals('Field "hash" must contain a valid sha1 hash', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordSetsSseType() {
        $mock = $this->getPasswordMock();

        $mock->expects($this->any())
             ->method('getSseType')
             ->will($this->onConsecutiveCalls('', EncryptionService::DEFAULT_SSE_ENCRYPTION));

        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setSseType')->with(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $this->validationService->validatePassword($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordCorrectsInvalidFolderUuid() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn('1-2-3');
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setFolder')->with(FolderService::BASE_FOLDER_UUID);
        $this->validationService->validatePassword($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordSetsEditedWhenEmpty() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(0);

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validatePassword($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidatePasswordSetsEditedWhenInFuture() {
        $mock = $this->getPasswordMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getHash')->willReturn(sha1('hash'));
        $mock->method('getFolder')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getStatus')->willReturn(2);
        $mock->method('getEdited')->willReturn(strtotime('+2 hours'));

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validatePassword($mock);
    }




    /**
     *
     * ValidateFolder Tests
     *
     */
    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderInvalidSse() {
        $mock = $this->getFolderMock();

        try {
            $this->validationService->validateFolder($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7b584c1e', $e->getId());
            $this->assertEquals('Invalid server side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderInvalidCse() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);

        try {
            $this->validationService->validateFolder($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderEmptyLabel() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);

        try {
            $this->validationService->validateFolder($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7c31eb4d', $e->getId());
            $this->assertEquals('Field "label" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderSetsSseType() {
        $mock = $this->getFolderMock();

        $mock->expects($this->any())
             ->method('getSseType')
             ->will($this->onConsecutiveCalls('', EncryptionService::DEFAULT_SSE_ENCRYPTION));

        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getParent')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setSseType')->with(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $this->validationService->validateFolder($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderCorrectsInvalidFolderUuid() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getParent')->willReturn('1-2-3');
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setParent')->with(FolderService::BASE_FOLDER_UUID);
        $this->validationService->validateFolder($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderSetsEditedWhenEmpty() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getParent')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getEdited')->willReturn(0);

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validateFolder($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderSetsEditedWhenInFuture() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getParent')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getEdited')->willReturn(strtotime('+2 hours'));

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validateFolder($mock);
    }




    /**
     *
     * ValidateTag Tests
     *
     */
    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateTagInvalidSse() {
        $mock = $this->getTagMock();

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7b584c1e', $e->getId());
            $this->assertEquals('Invalid server side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateTagInvalidCse() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateTagEmptyLabel() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7c31eb4d', $e->getId());
            $this->assertEquals('Field "label" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateTagEmptyColor() {
        $mock = $this->getTagMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');

        try {
            $this->validationService->validateTag($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('2aff026c', $e->getId());
            $this->assertEquals('Field "color" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
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
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
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
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
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
     * Domain Validation
     */

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateDomainValid() {
        $this->assertEquals(
            true,
            $this->validationService->isValidDomain('www.google.com')
        );
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateDomainInvalid() {
        $this->assertEquals(
            false,
            $this->validationService->isValidDomain('wwwgooglecom')
        );
    }




    /**
     * Uuid Validation
     */
    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateUuidValid() {
        $this->assertEquals(
            true,
            $this->validationService->isValidUuid(FolderService::BASE_FOLDER_UUID)
        );
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateUuidInvalid() {
        $this->assertEquals(
            false,
            $this->validationService->isValidUuid('not-a-uuid')
        );
    }




    /**
     * @return \OCA\Passwords\Db\PasswordRevision
     */
    protected function getPasswordMock() {
        $mock = $this
            ->getMockBuilder('\OCA\Passwords\Db\PasswordRevision')
            ->setMethods(['getSseType', 'setSseType', 'getHidden', 'getCseType', 'getLabel', 'getHash', 'getFolder', 'setFolder', 'getStatus', 'getEdited', 'setEdited'])
            ->getMock();

        $mock->method('getHidden')->willReturn(false);

        return $mock;
    }

    /**
     * @return \OCA\Passwords\Db\FolderRevision
     */
    protected function getFolderMock() {
        $mock = $this
            ->getMockBuilder('\OCA\Passwords\Db\FolderRevision')
            ->setMethods(['getSseType', 'setSseType', 'getCseType', 'getHidden', 'getLabel', 'getParent', 'setParent', 'getEdited', 'setEdited'])
            ->getMock();

        $mock->method('getHidden')->willReturn(false);

        return $mock;
    }

    /**
     * @return \OCA\Passwords\Db\TagRevision
     */
    protected function getTagMock() {
        $mock = $this
            ->getMockBuilder('\OCA\Passwords\Db\TagRevision')
            ->setMethods(['getSseType', 'setSseType', 'getCseType', 'getHidden', 'getLabel', 'getColor', 'getEdited', 'setEdited'])
            ->getMock();

        $mock->method('getHidden')->willReturn(false);

        return $mock;
    }
}