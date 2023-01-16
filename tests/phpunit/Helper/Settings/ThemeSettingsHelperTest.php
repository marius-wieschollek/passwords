<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OC_Defaults;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\ConfigurationService;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ThemeSettingsHelperTest
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ThemeSettingsHelperTest extends TestCase {

    /**
     * @var ThemeSettingsHelper
     */
    protected $themeSettingsHelper;

    /**
     * @var MockObject|ConfigurationService
     */
    protected $configurationService;

    /**
     * @var OC_Defaults
     */
    protected $themingDefaults;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     *
     */
    protected function setUp(): void {
        $this->configurationService = $this->createMock(ConfigurationService::class);
        $this->themingDefaults      = $this->createMock(\OC_Defaults::class);
        $this->urlGenerator         = $this->createMock(IURLGenerator::class);
        $this->themeSettingsHelper  = new ThemeSettingsHelper($this->configurationService, $this->themingDefaults, $this->urlGenerator);
    }

    /**
     * Test if default text color is returned correctly
     */
    public function testGetTextColor() {
        $this->themingDefaults->method('getTextColorPrimary')->willReturn('#000000');
        $this->themingDefaults->expects($this->once())->method('getTextColorPrimary');

        $result = $this->themeSettingsHelper->get('color.text');
        self::assertEquals('#000000', $result);
    }

    /**
     * Test if default primary color is returned correctly
     */
    public function testGetPrimaryColor() {
        $this->configurationService->method('isAppEnabled')->with('breezedark')->willReturn(false);
        $this->themingDefaults->method('getColorPrimary')->willReturn('#0082c9');
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('breezedark');
        $this->themingDefaults->expects($this->once())->method('getColorPrimary');

        $result = $this->themeSettingsHelper->get('color.primary');
        self::assertEquals('#0082c9', $result);
    }

    /**
     * Test if breezedark primary color is returned correctly when the app is enabled
     */
    public function testGetPrimaryColorWithBreezedarkTheme() {
        $this->configurationService->method('isAppEnabled')->with('breezedark')->willReturn(true);
        $this->themingDefaults->method('getColorPrimary')->willReturn('#0082c9');
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('breezedark');
        $this->themingDefaults->expects($this->never())->method('getColorPrimary');

        $result = $this->themeSettingsHelper->get('color.primary');
        self::assertEquals('#3daee9', $result);
    }

    /**
     * Test if default background color is returned correctly
     */
    public function testGetBackgroundColor() {
        $this->configurationService->method('getUserValue')->with('theme', 'none', null, 'accessibility')->willReturn('none');
        $this->configurationService->method('isAppEnabled')->with('breezedark')->willReturn(false);

        $this->configurationService->expects($this->once())->method('getUserValue')->with('theme', 'none', null, 'accessibility');
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('breezedark');

        $result = $this->themeSettingsHelper->get('color.background');
        self::assertEquals('#ffffff', $result);
    }

    /**
     * Test if accessibility background color is returned if dark theme enabled
     */
    public function testGetBackgroundColorWithAccessibility() {
        $this->configurationService->method('getUserValue')->with('theme', 'none', null, 'accessibility')->willReturn('themedark');
        $this->configurationService->method('isAppEnabled')->with('breezedark')->willReturn(false);

        $this->configurationService->expects($this->once())->method('getUserValue')->with('theme', 'none', null, 'accessibility');
        $this->configurationService->expects($this->never())->method('isAppEnabled');

        $result = $this->themeSettingsHelper->get('color.background');
        self::assertEquals('#181818', $result);
    }

    /**
     * Test if dark theme background color is returned if dark theme enabled
     */
    public function testGetBackgroundColorWithDarkTheme() {
        $this->configurationService->method('getUserValue')->with('theme', 'none', null, 'accessibility')->willReturn('dark');
        $this->configurationService->method('isAppEnabled')->with('breezedark')->willReturn(false);

        $this->configurationService->expects($this->once())->method('getUserValue')->with('theme', 'none', null, 'accessibility');
        $this->configurationService->expects($this->never())->method('isAppEnabled');

        $result = $this->themeSettingsHelper->get('color.background');
        self::assertEquals('#181818', $result);
    }

    /**
     * Test if breezedark primary color is returned correctly when the app is enabled
     */
    public function testGetBackgroundColorWithBreezedarkTheme() {
        $this->configurationService->method('getUserValue')->with('theme', 'none', null, 'accessibility')->willReturn('none');
        $this->configurationService->method('isAppEnabled')->with('breezedark')->willReturn(true);

        $this->configurationService->expects($this->once())->method('getUserValue')->with('theme', 'none', null, 'accessibility');
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('breezedark');

        $result = $this->themeSettingsHelper->get('color.background');
        self::assertEquals('#31363b', $result);
    }

    /**
     * Test if server name is returned correctly
     */
    public function testGetLabel() {
        $this->themingDefaults->method('getEntity')->willReturn('Nextcloud');
        $this->themingDefaults->expects($this->once())->method('getEntity');

        $result = $this->themeSettingsHelper->get('label');
        self::assertEquals('Nextcloud', $result);
    }

    /**
     * Test if the default background image is returned correctly
     */
    public function testGetBackgroundImage() {
        $this->urlGenerator->method('imagePath')->with('core', 'app-background.jpg')->willReturn('/core/img/app-background.jpg');
        $this->urlGenerator->method('getAbsoluteURL')->with('/core/img/app-background.jpg')->willReturn('https://cloud.com/core/img/app-background.jpg');

        $this->urlGenerator->expects($this->once())->method('imagePath')->with('core', 'app-background.jpg');
        $this->urlGenerator->expects($this->once())->method('getAbsoluteURL')->with('/core/img/app-background.jpg');

        $this->configurationService->method('isAppEnabled')->with('unsplash')->willReturn(false);
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('unsplash');

        $result = $this->themeSettingsHelper->get('background');
        self::assertEquals('https://cloud.com/core/img/app-background.jpg', $result);
    }

    /**
     * Test if background image from theme is returned if OC_Defaults supports it
     */
    public function testGetThemedBackgroundImage() {
        $themingDefaults = $this->getMockBuilder(OC_Defaults::class)
                                ->setMethods(['getBackground'])
                                ->getMock();
        $themingDefaults->method('getBackground')->willReturn('/theming/img/background.png');

        $this->urlGenerator->method('imagePath')->with('core', 'background.png')->willReturn('/core/img/background.png');
        $this->urlGenerator->method('getAbsoluteURL')->with('/theming/img/background.png')->willReturn('https://cloud.com/theming/img/background.png');
        $this->urlGenerator->expects($this->never())->method('imagePath');
        $this->urlGenerator->expects($this->once())->method('getAbsoluteURL')->with('/theming/img/background.png');

        $this->configurationService->method('isAppEnabled')->with('unsplash')->willReturn(false);
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('unsplash');

        $themeSettingsHelper = new ThemeSettingsHelper($this->configurationService, $themingDefaults, $this->urlGenerator);
        $result              = $themeSettingsHelper->get('background');
        self::assertEquals('https://cloud.com/theming/img/background.png', $result);
    }

    /**
     * Test if unspash image is returned when the app is enabled
     */
    public function testGetSplashBackgroundImage() {
        $usSettings = $this->createMock(\OCA\Unsplash\Services\SettingsService::class);
        $usSettings->method('headerbackgroundLink')->willReturn('https://source.unsplash.com/random/featured/?nature');

        \OC::$server = $this->createMock(\OC\Server::class);
        \OC::$server->method('get')->willReturn($usSettings);

        $this->urlGenerator->method('imagePath')->with('core', 'background.png')->willReturn('/core/img/background.png');
        $this->urlGenerator->method('getAbsoluteURL')->with('/core/img/background.png')->willReturn('https://cloud.com/core/img/background.png');

        $this->configurationService->method('isAppEnabled')->with('unsplash')->willReturn(true);
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('unsplash');

        $result = $this->themeSettingsHelper->get('background');
        self::assertEquals('https://source.unsplash.com/random/featured/?nature', $result);
    }

    /**
     * Test if the logo is returned correctly
     */
    public function testGetLogo() {
        $this->themingDefaults->method('getLogo')->willReturn('/core/img/logo.svg');
        $this->urlGenerator->method('getAbsoluteURL')->with('/core/img/logo.svg')->willReturn('https://cloud.com/core/img/logo.svg');

        $this->urlGenerator->expects($this->once())->method('getAbsoluteURL')->with('/core/img/logo.svg');

        $result = $this->themeSettingsHelper->get('logo');
        self::assertEquals('https://cloud.com/core/img/logo.svg', $result);
    }

    /**
     * Test if the default app icon is returned correctly
     */
    public function testGetAppIcon() {
        $this->configurationService->method('isAppEnabled')->with('theming')->willReturn(false);
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('theming');

        $this->urlGenerator->method('imagePath')->with(Application::APP_NAME, 'app-themed.svg')->willReturn('/apps/passwords/app-themed.svg');
        $this->urlGenerator->method('getAbsoluteURL')->with('/apps/passwords/app-themed.svg')->willReturn('https://cloud.com/apps/passwords/app-themed.svg');

        $this->urlGenerator->expects($this->once())->method('imagePath')->with(Application::APP_NAME, 'app-themed.svg');
        $this->urlGenerator->expects($this->once())->method('getAbsoluteURL')->with('/apps/passwords/app-themed.svg');

        $result = $this->themeSettingsHelper->get('app.icon');
        self::assertEquals('https://cloud.com/apps/passwords/app-themed.svg', $result);
    }

    /**
     * Test if the themed app icon is returned correctly
     */
    public function testGetThemedAppIcon() {
        $this->configurationService->method('isAppEnabled')->with('theming')->willReturn(true);
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('theming');
        $this->configurationService->method('getAppValue')->with('cachebuster', '0', 'theming')->willReturn('2');
        $this->configurationService->expects($this->once())->method('getAppValue')->with('cachebuster', '0', 'theming');

        $this->urlGenerator->method('linkToRouteAbsolute')
                           ->with('theming.Icon.getThemedIcon', ['app' => Application::APP_NAME, 'image' => 'app-themed.svg', 'v' => '2'])
                           ->willReturn('https://cloud.com/apps/theming/passwords/app-themed.svg');

        $this->urlGenerator->expects($this->once())
                           ->method('linkToRouteAbsolute')
                           ->with('theming.Icon.getThemedIcon', ['app' => Application::APP_NAME, 'image' => 'app-themed.svg', 'v' => '2']);
        $this->urlGenerator->expects($this->never())->method('imagePath');
        $this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

        $result = $this->themeSettingsHelper->get('app.icon');
        self::assertEquals('https://cloud.com/apps/theming/passwords/app-themed.svg', $result);
    }

    /**
     * Test if the default folder icon is returned correctly
     */
    public function testGetFolderIcon() {
        $this->configurationService->method('isAppEnabled')->with('theming')->willReturn(false);
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('theming');

        $this->urlGenerator->method('imagePath')->with('core', 'filetypes/folder.svg')->willReturn('/core/img/filetypes/folder.svg');
        $this->urlGenerator->method('getAbsoluteURL')->with('/core/img/filetypes/folder.svg')->willReturn('https://cloud.com/core/img/filetypes/folder.svg');

        $this->urlGenerator->expects($this->once())->method('imagePath')->with('core', 'filetypes/folder.svg');
        $this->urlGenerator->expects($this->once())->method('getAbsoluteURL')->with('/core/img/filetypes/folder.svg');

        $result = $this->themeSettingsHelper->get('folder.icon');
        self::assertEquals('https://cloud.com/core/img/filetypes/folder.svg', $result);
    }

    /**
     * Test if the themed folder icon is returned correctly
     */
    public function testGetThemedFolderIcon() {
        $this->configurationService->method('isAppEnabled')->with('theming')->willReturn(true);
        $this->configurationService->expects($this->once())->method('isAppEnabled')->with('theming');
        $this->configurationService->method('getAppValue')->with('cachebuster', '0', 'theming')->willReturn('2');
        $this->configurationService->expects($this->once())->method('getAppValue')->with('cachebuster', '0', 'theming');

        $this->urlGenerator->method('linkToRouteAbsolute')
                           ->with('theming.Icon.getThemedIcon', ['app' => 'core', 'image' => 'filetypes/folder.svg', 'v' => '2'])
                           ->willReturn('https://cloud.com/apps/theming/filetypes/folder.svg');

        $this->urlGenerator->expects($this->once())
                           ->method('linkToRouteAbsolute')
                           ->with('theming.Icon.getThemedIcon', ['app' => 'core', 'image' => 'filetypes/folder.svg', 'v' => '2']);
        $this->urlGenerator->expects($this->never())->method('imagePath');
        $this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

        $result = $this->themeSettingsHelper->get('folder.icon');
        self::assertEquals('https://cloud.com/apps/theming/filetypes/folder.svg', $result);
    }

    /**
     * Test if the list theme settings method works
     */
    public function testListThemeSettings() {
        $expected = [
            'server.theme.color.primary'    => '#0082c9',
            'server.theme.color.text'       => '#ffffff',
            'server.theme.color.background' => '#ffffff',
            'server.theme.background'       => 'https://cloud.com/core/img/app-background.jpg',
            'server.theme.logo'             => 'https://cloud.com/core/img/logo.svg',
            'server.theme.label'            => 'Nextcloud',
            'server.theme.app.icon'         => 'https://cloud.com/apps/passwords/app-themed.svg',
            'server.theme.folder.icon'      => 'https://cloud.com/core/img/filetypes/folder.svg'
        ];

        $this->configurationService->method('isAppEnabled')->willReturn(false);
        $this->configurationService->method('getUserValue')->with('theme', 'none', null, 'accessibility')->willReturn('none');

        $this->themingDefaults->method('getColorPrimary')->willReturn('#0082c9');
        $this->themingDefaults->method('getTextColorPrimary')->willReturn('#ffffff');
        $this->themingDefaults->method('getLogo')->willReturn('/core/img/logo.svg');
        $this->themingDefaults->method('getEntity')->willReturn('Nextcloud');

        $this->urlGenerator->method('imagePath')->willReturnMap(
            [
                ['core', 'app-background.jpg', '/core/img/app-background.jpg'],
                [Application::APP_NAME, 'app-themed.svg', '/apps/passwords/app-themed.svg'],
                ['core', 'filetypes/folder.svg', '/core/img/filetypes/folder.svg']
            ]
        );

        $this->urlGenerator->method('getAbsoluteURL')->willReturnMap(
            [
                ['/core/img/app-background.jpg', 'https://cloud.com/core/img/app-background.jpg'],
                ['/core/img/logo.svg', 'https://cloud.com/core/img/logo.svg'],
                ['/apps/passwords/app-themed.svg', 'https://cloud.com/apps/passwords/app-themed.svg'],
                ['/core/img/filetypes/folder.svg', 'https://cloud.com/core/img/filetypes/folder.svg'],
            ]
        );

        $result = $this->themeSettingsHelper->list();
        self::assertEquals($expected, $result);
    }
}