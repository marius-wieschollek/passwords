<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OCA\Passwords\Services\ConfigurationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class UserSettingsHelperTest
 *
 * @package OCA\Passwords\Helper\Settings
 */
class UserSettingsHelperTest extends TestCase {

    /**
     * @var UserSettingsHelper
     */
    protected $userSettingsHelper;

    /**
     * @var MockObject|ConfigurationService
     */
    protected $configurationService;

    /**
     *
     */
    protected function setUp(): void {
        $this->configurationService = $this->createMock(ConfigurationService::class);
        $this->userSettingsHelper   = new UserSettingsHelper($this->configurationService);
    }

    /**
     * Test get with custom value and no user id
     *
     * @throws \Exception
     */
    public function testGetWithDefaultValue() {
        $this->configurationService->method('getUserValue')->willReturn('1');
        $this->configurationService->expects($this->once())->method('getUserValue')->with('password/generator/strength', 1, null);

        $result = $this->userSettingsHelper->get('password.generator.strength');
        self::assertEquals(1, $result);
    }

    /**
     * Test get with no user value and user id
     *
     * @throws \Exception
     */
    public function testGetWithDefaultValueAndUser() {
        $this->configurationService->method('getUserValue')->willReturn('1');
        $this->configurationService->expects($this->once())->method('getUserValue')->with('password/generator/strength', 1, 'user');

        $result = $this->userSettingsHelper->get('password.generator.strength','user');
        self::assertEquals(1, $result);
    }

    /**
     * Test get with custom value and no user id
     *
     * @throws \Exception
     */
    public function testGetWithUserValue() {
        $this->configurationService->method('getUserValue')->willReturn('4');
        $this->configurationService->expects($this->once())->method('getUserValue')->with('password/generator/strength', 1, null);

        $result = $this->userSettingsHelper->get('password.generator.strength');
        self::assertEquals(4, $result);
    }

    /**
     * Test get with custom value and user id
     *
     * @throws \Exception
     */
    public function testGetWithUserValueAndUser() {
        $this->configurationService->method('getUserValue')->willReturn('4');
        $this->configurationService->expects($this->once())->method('getUserValue')->with('password/generator/strength', 1, 'user');

        $result = $this->userSettingsHelper->get('password.generator.strength','user');
        self::assertEquals(4, $result);
    }

    /**
     * Test if the system wide default setting is respected for mail.security
     *
     * @throws \Exception
     */
    public function testMailSecuritySystemSettingsRespected() {
        $this->configurationService->method('getAppValue')->willReturn('0');
        $this->configurationService->method('getUserValue')->willReturnMap(
            [
                ['mail/security', '0', null, 'passwords', '0'],
                ['mail/security', '1', null, 'passwords', '1']
            ]
        );
        $this->configurationService->expects($this->once())->method('getAppValue')->with('settings/mail/security', true);
        $this->configurationService->expects($this->once())->method('getUserValue')->with('mail/security', '0', null);

        $result = $this->userSettingsHelper->get('mail.security');
        self::assertEquals(false, $result);
    }

    /**
     * Test if the user setting is respected for mail.security
     *
     * @throws \Exception
     */
    public function testMailSecurityUserSettingsRespected() {
        $this->configurationService->method('getAppValue')->willReturn('0');
        $this->configurationService->method('getUserValue')->willReturnMap(
            [
                ['mail/security', '0', null, 'passwords', '1'],
                ['mail/security', '1', null, 'passwords', '0']
            ]
        );
        $this->configurationService->expects($this->once())->method('getAppValue')->with('settings/mail/security', true);
        $this->configurationService->expects($this->once())->method('getUserValue')->with('mail/security', '0', null);

        $result = $this->userSettingsHelper->get('mail.security');
        self::assertEquals(true, $result);
    }

    /**
     * Test if the system wide default setting is respected for mail.shares
     *
     * @throws \Exception
     */
    public function testMailSharesSystemSettingsRespected() {
        $this->configurationService->method('getAppValue')->willReturn('0');
        $this->configurationService->method('getUserValue')->willReturnMap(
            [
                ['mail/shares', '0', null, 'passwords', '0'],
                ['mail/shares', '1', null, 'passwords', '1']
            ]
        );
        $this->configurationService->expects($this->once())->method('getAppValue')->with('settings/mail/shares', false);
        $this->configurationService->expects($this->once())->method('getUserValue')->with('mail/shares', '0', null);

        $result = $this->userSettingsHelper->get('mail.shares');
        self::assertEquals(false, $result);
    }

    /**
     * Test if the user setting is respected for mail.shares
     *
     * @throws \Exception
     */
    public function testMailSharesUserSettingsRespected() {
        $this->configurationService->method('getAppValue')->willReturn('0');
        $this->configurationService->method('getUserValue')->willReturnMap(
            [
                ['mail/shares', '0', null, 'passwords', '1'],
                ['mail/shares', '1', null, 'passwords', '0']
            ]
        );
        $this->configurationService->expects($this->once())->method('getAppValue')->with('settings/mail/shares', false);
        $this->configurationService->expects($this->once())->method('getUserValue')->with('mail/shares', '0', null);

        $result = $this->userSettingsHelper->get('mail.shares');
        self::assertEquals(true, $result);
    }

    /**
     * Test if the automatic detection words for encryption if cse available
     *
     * @throws \Exception
     */
    public function testEncryptionDefaultValueCSE() {
        $this->configurationService->method('hasUserValue')->willReturn(true);
        $this->configurationService->method('getUserValue')->willReturnMap(
            [
                ['encryption/cse', 0, null, 'passwords', '0'],
                ['encryption/cse', 1, null, 'passwords', '1']
            ]
        );
        $this->configurationService->expects($this->once())->method('hasUserValue')->with('user/challenge/id', null);
        $this->configurationService->expects($this->once())->method('getUserValue')->with('encryption/cse', '1', null);

        $result = $this->userSettingsHelper->get('encryption.cse');
        self::assertEquals(1, $result);
    }

    /**
     * Test if the automatic detection words for encryption if no cse available
     *
     * @throws \Exception
     */
    public function testEncryptionDefaultValueNoCSE() {
        $this->configurationService->method('hasUserValue')->willReturn(false);
        $this->configurationService->method('getUserValue')->willReturnMap(
            [
                ['encryption/cse', 0, null, 'passwords', '0'],
                ['encryption/cse', 1, null, 'passwords', '1']
            ]
        );
        $this->configurationService->expects($this->once())->method('hasUserValue')->with('user/challenge/id', null);
        $this->configurationService->expects($this->once())->method('getUserValue')->with('encryption/cse', '0', null);

        $result = $this->userSettingsHelper->get('encryption.cse');
        self::assertEquals(0, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetSessionLifetime() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('session/lifetime', 30, null);

        $result = $this->userSettingsHelper->set('session.lifetime', 30);
        self::assertEquals(30, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetInvalidSessionLifetime() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('session/lifetime', 600, null);

        $result = $this->userSettingsHelper->set('session.lifetime', 29);
        self::assertEquals(600, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetPasswordGeneratorStrength() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('password/generator/strength', 2, null);

        $result = $this->userSettingsHelper->set('password.generator.strength', 2);
        self::assertEquals(2, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetPasswordGeneratorStrengthTooLow() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('password/generator/strength', 1, null);

        $result = $this->userSettingsHelper->set('password.generator.strength', -1);
        self::assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetPasswordGeneratorStrengthTooHigh() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('password/generator/strength', 1, null);

        $result = $this->userSettingsHelper->set('password.generator.strength', 5);
        self::assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetPasswordSecurityAge() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('password/security/age', 1, null);

        $result = $this->userSettingsHelper->set('password.security.age', 1);
        self::assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetPasswordSecurityAgeToZero() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('password/security/age', 0, null);

        $result = $this->userSettingsHelper->set('password.security.age', 0);
        self::assertEquals(0, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetInvalidPasswordSecurityAge() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('password/security/age', 0, null);

        $result = $this->userSettingsHelper->set('password.security.age', -1);
        self::assertEquals(0, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetSseEncryption() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('encryption/sse', 2, null);

        $result = $this->userSettingsHelper->set('encryption.sse', 2);
        self::assertEquals(2, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetSseEncryptionTooLow() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('encryption/sse', 0, null);

        $result = $this->userSettingsHelper->set('encryption.sse', -1);
        self::assertEquals(0, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetSseEncryptionTooHigh() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('encryption/sse', 0, null);

        $result = $this->userSettingsHelper->set('encryption.sse', 3);
        self::assertEquals(0, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetCseEncryption() {
        $this->configurationService->expects($this->once())->method('setUserValue')->with('encryption/cse', 1, null);

        $result = $this->userSettingsHelper->set('encryption.cse', 1);
        self::assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetCseEncryptionTooLow() {
        $this->configurationService->method('hasUserValue')->with('user/challenge/id', null)->willReturn(true);
        $this->configurationService->expects($this->once())->method('setUserValue')->with('encryption/cse', 1, null);

        $result = $this->userSettingsHelper->set('encryption.cse', -1);
        self::assertEquals(1, $result);
    }

    /**
     * @throws \Exception
     */
    public function testSetCseEncryptionTooHigh() {
        $this->configurationService->method('hasUserValue')->with('user/challenge/id', null)->willReturn(true);
        $this->configurationService->expects($this->once())->method('setUserValue')->with('encryption/cse', 1, null);

        $result = $this->userSettingsHelper->set('encryption.cse', 2);
        self::assertEquals(1, $result);
    }
}