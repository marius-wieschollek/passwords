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
 * Class ValidationServiceTest
 *
 * @package OCA\Passwords\Services
 * @covers \OCA\Passwords\Services\ValidationService
 */
class ValidationServiceTest extends TestCase {

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
}