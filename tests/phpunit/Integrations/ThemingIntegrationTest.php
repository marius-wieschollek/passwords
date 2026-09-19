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

namespace OCA\Passwords\Integrations;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Theming\ThemingColorHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Theming\Capabilities;
use OCA\Theming\ThemingDefaults;
use OCP\IURLGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ThemingIntegrationTest
 *
 * @package OCA\Passwords\Integrations
 */
class ThemingIntegrationTest extends TestCase {

    const array CAPABILITIES
        = [
            'name'             => 'capability-name',
            'color'            => '#c01021',
            'color-text'       => '#c02022',
            'logo'             => 'https://example.com/capability-logo.png',
            'background'       => 'https://example.com/capability-background.webp',
            'background-text'  => '#c06026',
            'background-plain' => false,
            'cacheBuster'      => 'capability-cache-buster',
        ];

    const string DEFAULT_BACKGROUND_URL = 'https://example.com/apps/theming/img/background/jo-myoung-hee-fluid.webp';

    /**
     * @var MockObject|ConfigurationService
     */
    protected $config;

    /**
     * @var MockObject|IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var MockObject|ThemingColorHelper
     */
    protected $colorHelper;

    /**
     * @var MockObject|ThemingDefaults
     */
    protected $themingDefaults;

    /**
     * @var MockObject|Capabilities
     */
    protected $capabilities;

    protected function setUp(): void {
        $this->config          = $this->createMock(ConfigurationService::class);
        $this->urlGenerator    = $this->createMock(IURLGenerator::class);
        $this->colorHelper     = $this->createMock(ThemingColorHelper::class);
        $this->themingDefaults = $this->createMock(ThemingDefaults::class);
        $this->capabilities    = $this->createMock(Capabilities::class);
    }

    public function testIsAvailableWhenAppEnabledAndThemingDefaultsRegistered() {
        $this->config->expects($this->once())->method('isAppEnabled')->with('theming')->willReturn(true);

        self::assertTrue($this->getThemingIntegration()->isAvailable());
    }

    public function testIsNotAvailableWhenThemingDefaultsMissing() {
        $this->config->expects($this->once())->method('isAppEnabled')->with('theming')->willReturn(true);

        self::assertFalse($this->getThemingIntegration($this->getContainer([]))->isAvailable());
    }

    public function testIsNotAvailableWhenAppDisabled() {
        $this->config->expects($this->once())->method('isAppEnabled')->with('theming')->willReturn(false);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('has');

        self::assertFalse($this->getThemingIntegration($container)->isAvailable());
    }

    public static function capabilityGetterProvider(): array {
        return [
            'logo'       => ['getLogoIcon', self::CAPABILITIES['logo']],
            'name'       => ['getName', self::CAPABILITIES['name']],
            'color-text' => ['getTextColorPrimary', self::CAPABILITIES['color-text']],
            'color'      => ['getColorPrimary', self::CAPABILITIES['color']],
            'bg-text'    => ['getColorBackground', self::CAPABILITIES['background-text']],
            'background' => ['getBackgroundImage', self::CAPABILITIES['background']],
        ];
    }

    #[DataProvider('capabilityGetterProvider')]
    public function testGetterReadsItsOwnCapability(string $method, string $expected) {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => self::CAPABILITIES]);
        $this->themingDefaults->expects($this->never())->method($this->anything());

        self::assertEquals($expected, $this->getThemingIntegration()->{$method}());
    }

    public function testGetLogoIconFallsBackToAbsoluteThemingDefaultsLogo() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => []]);
        $this->themingDefaults->expects($this->once())->method('getLogo')->willReturn('/core/img/logo.png');
        $this->urlGenerator->expects($this->once())
                           ->method('getAbsoluteURL')->with('/core/img/logo.png')
                           ->willReturn('https://example.com/core/img/logo.png');

        self::assertEquals('https://example.com/core/img/logo.png', $this->getThemingIntegration()->getLogoIcon());
    }

    public function testGetNameFallsBackToThemingDefaults() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => []]);
        $this->themingDefaults->expects($this->once())->method('getName')->willReturn('Defaults Name');

        self::assertEquals('Defaults Name', $this->getThemingIntegration()->getName());
    }

    public function testGetTextColorPrimaryFallsBackToThemingDefaults() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => []]);
        $this->themingDefaults->expects($this->once())->method('getTextColorPrimary')->willReturn('#d01031');

        self::assertEquals('#d01031', $this->getThemingIntegration()->getTextColorPrimary());
    }

    public function testGetColorPrimaryFallsBackToThemingDefaults() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => []]);
        $this->themingDefaults->expects($this->once())->method('getColorPrimary')->willReturn('#d02032');

        self::assertEquals('#d02032', $this->getThemingIntegration()->getColorPrimary());
    }

    public function testGetColorBackgroundFallsBackToContrastColor() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => []]);
        $this->themingDefaults->expects($this->once())->method('getColorBackground')->willReturn('#0082c9');
        $this->colorHelper->expects($this->once())->method('getTextColor')->with('#0082c9')->willReturn('#000000');

        self::assertEquals('#000000', $this->getThemingIntegration()->getColorBackground());
    }

    public function testGetColorBackgroundTreatsEmptyCapabilityAsAbsent() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => ['background-text' => '']]);
        $this->themingDefaults->expects($this->once())->method('getColorBackground')->willReturn('#ffffff');
        $this->colorHelper->expects($this->once())->method('getTextColor')->with('#ffffff')->willReturn('#000000');

        self::assertEquals('#000000', $this->getThemingIntegration()->getColorBackground());
    }

    public function testCapabilitiesAreNotResolvedWhenNotRegistered() {
        $container = $this->getContainer([ThemingDefaults::class => $this->themingDefaults]);

        $this->capabilities->expects($this->never())->method('getCapabilities');
        $this->themingDefaults->expects($this->once())->method('getColorPrimary')->willReturn('#d03033');

        self::assertEquals('#d03033', $this->getThemingIntegration($container)->getColorPrimary());
    }

    public function testGetBackgroundImageLeavesAbsoluteUrlUntouched() {
        $this->capabilities->method('getCapabilities')->willReturn(
            ['theming' => ['background-plain' => false, 'background' => 'https://example.com/bg.webp']]
        );
        $this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

        self::assertEquals('https://example.com/bg.webp', $this->getThemingIntegration()->getBackgroundImage());
    }

    public function testGetBackgroundImageAbsolutizesRelativeUrl() {
        $this->capabilities->method('getCapabilities')->willReturn(
            ['theming' => ['background-plain' => false, 'background' => '/apps/theming/img/background/x.webp']]
        );
        $this->urlGenerator->expects($this->once())
                           ->method('getAbsoluteURL')->with('/apps/theming/img/background/x.webp')
                           ->willReturn('https://example.com/apps/theming/img/background/x.webp');

        self::assertEquals(
            'https://example.com/apps/theming/img/background/x.webp',
            $this->getThemingIntegration()->getBackgroundImage()
        );
    }

    public function testGetBackgroundImageIgnoresPlainBackgroundColor() {
        $this->capabilities->method('getCapabilities')->willReturn(
            ['theming' => ['background-plain' => true, 'background' => '#0082c9']]
        );
        $this->expectDefaultBackground();

        self::assertEquals(self::DEFAULT_BACKGROUND_URL, $this->getThemingIntegration()->getBackgroundImage());
    }

    public function testGetBackgroundImageFallsBackWhenCapabilityEmpty() {
        $this->capabilities->method('getCapabilities')->willReturn(
            ['theming' => ['background-plain' => false, 'background' => '']]
        );
        $this->expectDefaultBackground();

        self::assertEquals(self::DEFAULT_BACKGROUND_URL, $this->getThemingIntegration()->getBackgroundImage());
    }

    public function testGetBackgroundImageFallsBackWhenCapabilitiesMissing() {
        $container = $this->getContainer([ThemingDefaults::class => $this->themingDefaults]);
        $this->expectDefaultBackground();

        self::assertEquals(
            self::DEFAULT_BACKGROUND_URL,
            $this->getThemingIntegration($container)->getBackgroundImage()
        );
    }

    public function testGetFolderIconBuildsThemedIconRoute() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => self::CAPABILITIES]);
        $this->urlGenerator->expects($this->once())
                           ->method('linkToRouteAbsolute')
                           ->with(
                               'theming.Icon.getThemedIcon',
                               ['app' => 'core', 'image' => 'filetypes/folder.svg', 'v' => 'capability-cache-buster']
                           )
                           ->willReturn('https://example.com/folder.svg');

        self::assertEquals('https://example.com/folder.svg', $this->getThemingIntegration()->getFolderIcon());
    }

    public function testGetAppIconBuildsThemedIconRoute() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => self::CAPABILITIES]);
        $this->urlGenerator->expects($this->once())
                           ->method('linkToRouteAbsolute')
                           ->with(
                               'theming.Icon.getThemedIcon',
                               ['app' => Application::APP_NAME, 'image' => 'app-themed.svg', 'v' => 'capability-cache-buster']
                           )
                           ->willReturn('https://example.com/app-themed.svg');

        self::assertEquals('https://example.com/app-themed.svg', $this->getThemingIntegration()->getAppIcon());
    }

    public function testGetAppIconWithoutCacheBuster() {
        $this->capabilities->method('getCapabilities')->willReturn(['theming' => []]);
        $this->urlGenerator->expects($this->once())
                           ->method('linkToRouteAbsolute')
                           ->with(
                               'theming.Icon.getThemedIcon',
                               ['app' => Application::APP_NAME, 'image' => 'app-themed.svg', 'v' => null]
                           )
                           ->willReturn('https://example.com/app-themed.svg');

        self::assertEquals('https://example.com/app-themed.svg', $this->getThemingIntegration()->getAppIcon());
    }

    public function testCapabilitiesAreFetchedOnlyOnce() {
        $this->capabilities->expects($this->once())
                           ->method('getCapabilities')->willReturn(['theming' => self::CAPABILITIES]);

        $integration = $this->getThemingIntegration();
        $integration->getColorPrimary();
        $integration->getTextColorPrimary();
        $integration->getName();
    }

    public function testEmptyCapabilitiesAreFetchedOnlyOnce() {
        $this->capabilities->expects($this->once())->method('getCapabilities')->willReturn(['theming' => []]);
        $this->themingDefaults->method('getColorPrimary')->willReturn('#d04034');
        $this->themingDefaults->method('getTextColorPrimary')->willReturn('#d05035');
        $this->themingDefaults->method('getName')->willReturn('Defaults Name');

        $integration = $this->getThemingIntegration();
        $integration->getColorPrimary();
        $integration->getTextColorPrimary();
        $integration->getName();
    }

    public function testMissingThemingSectionIsHandledGracefully() {
        $this->capabilities->method('getCapabilities')->willReturn(['core' => []]);
        $this->themingDefaults->expects($this->once())->method('getColorPrimary')->willReturn('#d06036');

        self::assertEquals('#d06036', $this->getThemingIntegration()->getColorPrimary());
    }

    /**
     * @param MockObject|ContainerInterface $container
     */
    protected function getThemingIntegration($container = null): ThemingIntegration {
        return new ThemingIntegration(
            $this->config,
            $this->urlGenerator,
            $this->colorHelper,
            $container ?? $this->getContainer(
                [
                    ThemingDefaults::class => $this->themingDefaults,
                    Capabilities::class    => $this->capabilities,
                ]
            )
        );
    }

    /**
     * @param array $services
     *
     * @return MockObject|ContainerInterface
     */
    protected function getContainer(array $services) {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('has')->willReturnCallback(
            fn(string $id): bool => array_key_exists($id, $services)
        );

        $container->method('get')->willReturnCallback(
            function (string $id) use ($services) {
                if(!array_key_exists($id, $services)) {
                    throw new class('Service not found: '.$id) extends \RuntimeException implements NotFoundExceptionInterface {
                    };
                }

                return $services[ $id ];
            }
        );

        return $container;
    }

    /**
     * @return void
     */
    protected function expectDefaultBackground(): void {
        $path = '/apps/theming/img/background/jo-myoung-hee-fluid.webp';

        $this->urlGenerator->expects($this->once())
                           ->method('linkTo')
                           ->with('theming', 'img/background/jo-myoung-hee-fluid.webp')
                           ->willReturn($path);

        $this->urlGenerator->expects($this->once())
                           ->method('getAbsoluteURL')->with($path)
                           ->willReturn(self::DEFAULT_BACKGROUND_URL);
    }
}