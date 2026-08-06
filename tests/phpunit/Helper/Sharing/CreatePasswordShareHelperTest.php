<?php
/*
 * @copyright 2026 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\Sharing;

use OCA\Passwords\Db\Share;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Settings\ShareSettingsHelper;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\ShareService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Class CreatePasswordShareHelperTest
 *
 * @covers \OCA\Passwords\Helper\Sharing\CreatePasswordShareHelper
 */
class CreatePasswordShareHelperTest extends TestCase {

    /**
     * @var MockObject|ShareSettingsHelper
     */
    protected $shareSettings;

    /**
     * @var CreatePasswordShareHelper
     */
    protected $createPasswordShareHelper;

    protected function setUp(): void {
        $this->shareSettings = $this->createMock(ShareSettingsHelper::class);

        $this->createPasswordShareHelper = new CreatePasswordShareHelper(
            $this->createMock(ShareService::class),
            $this->shareSettings,
            $this->createMock(PasswordService::class),
            $this->createMock(PasswordRevisionService::class)
        );
    }

    public function testUserSharesAreAlwaysAllowed(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(false);

        $this->checkType(Share::TYPE_USER);
        $this->addToAssertionCount(1);
    }

    public function testGroupSharesAreAllowedIfGroupSharingIsEnabled(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(true);

        $this->checkType(Share::TYPE_GROUP);
        $this->addToAssertionCount(1);
    }

    public function testGroupSharesAreRejectedIfGroupSharingIsDisabled(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(false);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid share type');
        $this->checkType(Share::TYPE_GROUP);
    }

    public function testLinkSharesAreStillRejected(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(true);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid share type');
        $this->checkType(Share::TYPE_LINK);
    }

    public function testUnknownSharesAreRejected(): void {
        $this->shareSettings->method('get')->with('groups.enabled')->willReturn(true);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid share type');
        $this->checkType('circle');
    }

    /**
     * checkType is protected because it is only used from within the share creation
     *
     * @param string $type
     *
     * @throws ApiException
     */
    protected function checkType(string $type): void {
        $method = new ReflectionMethod($this->createPasswordShareHelper, 'checkType');
        $method->setAccessible(true);
        $method->invoke($this->createPasswordShareHelper, $type);
    }
}
