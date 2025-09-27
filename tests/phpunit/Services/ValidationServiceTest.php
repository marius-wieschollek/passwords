<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Services\Object\FolderService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

/**
 * Class ValidationServiceTest
 *
 * @package OCA\Passwords\Services
 * @covers  \OCA\Passwords\Services\ValidationService
 */
class ValidationServiceTest extends TestCase {

    /**
     * @var ValidationService
     */
    protected $validationService;

    /**
     *
     */
    protected function setUp(): void {
        $container               = $this->createMock(ContainerInterface::class);
        $this->validationService = new ValidationService($container);
    }


    /**
     * Domain Validation
     */

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateDomainValid() {
        $this->assertEquals(
            true,
            $this->validationService->isValidDomain('www.google.com')
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
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
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateUuidValid() {
        $this->assertEquals(
            true,
            $this->validationService->isValidUuid(FolderService::BASE_FOLDER_UUID)
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testValidateUuidInvalid() {
        $this->assertEquals(
            false,
            $this->validationService->isValidUuid('not-a-uuid')
        );
    }
}