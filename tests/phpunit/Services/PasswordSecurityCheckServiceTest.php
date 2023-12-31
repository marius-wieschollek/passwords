<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Helper\SecurityCheck\PasswordDatabaseUpdateHelper;
use OCA\Passwords\Helper\SecurityCheck\UserRulesSecurityCheck;
use OCA\Passwords\Provider\SecurityCheck\SecurityCheckProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PasswordSecurityCheckServiceTest extends TestCase {
    /**
     * @var SecurityCheckProviderInterface|MockObject
     */
    protected SecurityCheckProviderInterface|MockObject $securityCheckProvider;

    /**
     * @var UserRulesSecurityCheck|MockObject
     */
    protected UserRulesSecurityCheck|MockObject $userRulesCheck;

    /**
     * @var PasswordDatabaseUpdateHelper|MockObject
     */
    protected PasswordDatabaseUpdateHelper|MockObject $passwordDatabaseUpdateHelper;

    /**
     * @var PasswordSecurityCheckService
     */
    protected PasswordSecurityCheckService $passwordSecurityCheckService;

    protected function setUp(): void {
        $this->securityCheckProvider        = $this->createMock(SecurityCheckProviderInterface::class);
        $this->userRulesCheck               = $this->createMock(UserRulesSecurityCheck::class);
        $this->passwordDatabaseUpdateHelper = $this->createMock(PasswordDatabaseUpdateHelper::class);

        $this->passwordSecurityCheckService = new PasswordSecurityCheckService(
            $this->securityCheckProvider,
            $this->userRulesCheck,
            $this->passwordDatabaseUpdateHelper
        );
    }

    public function testGetRevisionSecurityLevelGood() {
        $passwordRevision = $this->getPasswordRevisionMock();
        $passwordRevision->method('getHash')->willReturn(sha1('secure-password'));

        $this->userRulesCheck->method('getRevisionSecurityLevel')->with($passwordRevision)->willReturn(null);
        $this->securityCheckProvider->method('isHashSecure')->with($passwordRevision->getHash())->willReturn(true);

        $result = $this->passwordSecurityCheckService->getRevisionSecurityLevel($passwordRevision);

        $this->assertSame([PasswordSecurityCheckService::LEVEL_OK, PasswordSecurityCheckService::STATUS_GOOD], $result);
    }

    public function testGetRevisionSecurityLevelBroken() {
        $passwordRevision = $this->getPasswordRevisionMock();
        $passwordRevision->method('getHash')->willReturn(sha1('secure-password'));

        $this->userRulesCheck->method('getRevisionSecurityLevel')->with($passwordRevision)->willReturn(null);
        $this->securityCheckProvider->method('isHashSecure')->with($passwordRevision->getHash())->willReturn(false);

        $result = $this->passwordSecurityCheckService->getRevisionSecurityLevel($passwordRevision);

        $this->assertSame([PasswordSecurityCheckService::LEVEL_BAD, PasswordSecurityCheckService::STATUS_BREACHED], $result);
    }

    public function testGetRevisionSecurityLevelUserRules() {
        $passwordRevision = $this->getPasswordRevisionMock();
        $passwordRevision->method('getHash')->willReturn(sha1('secure-password'));

        $userRulesResult = [PasswordSecurityCheckService::LEVEL_WEAK, PasswordSecurityCheckService::STATUS_DUPLICATE];
        $this->userRulesCheck->method('getRevisionSecurityLevel')->with($passwordRevision)->willReturn($userRulesResult);
        $this->securityCheckProvider->method('isHashSecure')->with($passwordRevision->getHash())->willReturn(true);

        $result = $this->passwordSecurityCheckService->getRevisionSecurityLevel($passwordRevision);

        $this->assertSame($userRulesResult, $result);
    }

    public function testGetRevisionSecurityLevelNoHash() {
        $passwordRevision = $this->getPasswordRevisionMock();
        $passwordRevision->method('getHash')->willReturn('');

        $this->userRulesCheck->method('getRevisionSecurityLevel')->with($passwordRevision)->willReturn(null);
        $this->securityCheckProvider->method('isHashSecure')->with($passwordRevision->getHash())->willReturn(false);

        $result = $this->passwordSecurityCheckService->getRevisionSecurityLevel($passwordRevision);

        $this->assertSame([PasswordSecurityCheckService::LEVEL_UNKNOWN, PasswordSecurityCheckService::STATUS_NOT_CHECKED], $result);
    }

    public function testIsPasswordSecureSecure() {
        $expectedResult = true;
        $password       = 'password';
        $this->securityCheckProvider->expects($this->once())->method('isPasswordSecure')->with($password)->willReturn($expectedResult);

        $result = $this->passwordSecurityCheckService->isPasswordSecure($password);

        $this->assertSame($expectedResult, $result);
    }

    public function testIsPasswordSecureInsecure() {
        $expectedResult = false;
        $password       = 'password';
        $this->securityCheckProvider->expects($this->once())->method('isPasswordSecure')->with($password)->willReturn($expectedResult);

        $result = $this->passwordSecurityCheckService->isPasswordSecure($password);

        $this->assertSame($expectedResult, $result);
    }

    public function testIsHashSecureSecure() {
        $expectedResult = true;
        $hash           = '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8';
        $this->securityCheckProvider->expects($this->once())->method('isHashSecure')->with($hash)->willReturn($expectedResult);

        $result = $this->passwordSecurityCheckService->isHashSecure($hash);

        $this->assertSame($expectedResult, $result);
    }

    public function testIsHashSecureInsecure() {
        $expectedResult = false;
        $hash           = '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8';
        $this->securityCheckProvider->expects($this->once())->method('isHashSecure')->with($hash)->willReturn($expectedResult);

        $result = $this->passwordSecurityCheckService->isHashSecure($hash);

        $this->assertSame($expectedResult, $result);
    }

    public function testGetHashRangeFullSecureHash() {
        $expectedResult = false;
        $hash           = '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8';
        $this->securityCheckProvider->expects($this->once())->method('isHashSecure')->with($hash)->willReturn($expectedResult);

        $result = $this->passwordSecurityCheckService->getHashRange($hash);

        $this->assertSame([$hash], $result);
    }

    public function testGetHashRangeFullInsecureHash() {
        $expectedResult = true;
        $hash           = '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8';
        $this->securityCheckProvider->expects($this->once())->method('isHashSecure')->with($hash)->willReturn($expectedResult);

        $result = $this->passwordSecurityCheckService->getHashRange($hash);

        $this->assertEmpty($result);
    }

    public function testGetHashRange() {
        $range  = '5baa6';
        $hashes = ['5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8'];
        $this->securityCheckProvider->expects($this->never())->method('isHashSecure');
        $this->securityCheckProvider->expects($this->once())->method('getHashRange')->with($range)->willReturn($hashes);

        $result = $this->passwordSecurityCheckService->getHashRange($range);

        $this->assertSame($hashes, $result);
    }

    public function testUpdateDb() {
        $this->passwordDatabaseUpdateHelper->expects($this->once())->method('updateDb');

        $this->passwordSecurityCheckService->updateDb();
    }

    /**
     * @return (PasswordRevision&MockObject)|MockObject
     */
    protected function getPasswordRevisionMock(): MockObject|PasswordRevision {
        $passwordRevision = $this
            ->getMockBuilder(PasswordRevision::class)
            ->addMethods(['getPassword', 'getHash'])
            ->getMock();

        return $passwordRevision;
    }
}