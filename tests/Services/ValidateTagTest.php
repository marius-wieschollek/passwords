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

/**
 * Class ValidateTagTest
 *
 * @package OCA\Passwords\Services
 * @covers \OCA\Passwords\Services\ValidationService
 */
class ValidateTagTest extends TestCase {

    /**
     * @var \OCA\Passwords\Services\ValidationService
     */
    protected $validationService;

    /**
     * @throws \ReflectionException
     */
    protected function setUp() {
        $container           = $this->createMock('\OCP\AppFramework\IAppContainer');
        $this->validationService = new \OCA\Passwords\Services\ValidationService($container);
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