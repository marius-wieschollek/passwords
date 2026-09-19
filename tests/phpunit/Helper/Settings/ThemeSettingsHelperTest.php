<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OC_Defaults;
use OC_Defaults_With_Everything;
use OC_Defaults_With_NoName;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Theming\ThemingColorHelper;
use OCA\Passwords\Integrations\ThemingIntegration;
use OCA\Passwords\Integrations\UnsplashIntegration;
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
     * @var MockObject|OC_Defaults
     */
    protected $themingDefaults;

    /**
     * @var MockObject|IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var MockObject|UnsplashIntegration
     */
    protected $unsplashIntegration;

    /**
     * @var MockObject|ThemingIntegration
     */
    protected $themingIntegration;

    /**
     * @var MockObject|ThemingColorHelper
     */
    protected $colorHelper;

    public function testGetPrimaryColorDefault() {
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getColorPrimary');

        $result = $this->getThemeSettingsHelper()->get('color.primary');
        self::assertEquals('#00679e', $result);
    }

    public function testGetPrimaryColorFromOcDefaults() {
        $this->themingDefaults = $this->createMock(OC_Defaults_With_Everything::class);

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getColorPrimary');

        $this->themingDefaults->expects($this->once())->method('getColorPrimary')->willReturn('#123456');

        $result = $this->getThemeSettingsHelper()->get('color.primary');
        self::assertEquals('#123456', $result);
    }

    public function testGetPrimaryColorFromTheming() {

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->themingIntegration->expects($this->once())->method('getColorPrimary')->willReturn('#123456');

        $result = $this->getThemeSettingsHelper()->get('color.primary');
        self::assertEquals('#123456', $result);
    }

    public function testGetTextColorDefault() {
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getTextColorPrimary');

        $result = $this->getThemeSettingsHelper()->get('color.text');
        self::assertEquals('#000000', $result);
    }

    public function testGetTextColorFromOcDefaults() {
        $this->themingDefaults = $this->createMock(OC_Defaults_With_Everything::class);

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getTextColorPrimary');

        $this->themingDefaults->expects($this->once())->method('getTextColorPrimary')->willReturn('#123456');

        $result = $this->getThemeSettingsHelper()->get('color.text');
        self::assertEquals('#123456', $result);
    }

    public function testGetTextColorFromTheming() {
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->themingIntegration->expects($this->once())->method('getTextColorPrimary')->willReturn('#123456');

        $result = $this->getThemeSettingsHelper()->get('color.text');
        self::assertEquals('#123456', $result);
    }

    public function testGetBackgroundColorDefault() {
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getColorBackground');

        $result = $this->getThemeSettingsHelper()->get('color.background');
        self::assertEquals('#ffffff', $result);
    }

    public function testGetBackgroundColorFromOcDefaultsWhite() {
        $this->themingDefaults = $this->createMock(OC_Defaults_With_Everything::class);

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getColorBackground');

        $this->themingDefaults->expects($this->once())->method('getColorBackground')->willReturn('#000000');
        $this->colorHelper->expects($this->once())->method('getTextColor')->with('#000000')->willReturn('#ffffff');

        $result = $this->getThemeSettingsHelper()->get('color.background');
        self::assertEquals('#ffffff', $result);
    }

    public function testGetBackgroundColorFromOcDefaultsBlack() {
        $this->themingDefaults = $this->createMock(OC_Defaults_With_Everything::class);

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getColorBackground');

        $this->themingDefaults->expects($this->once())->method('getColorBackground')->willReturn('#ffffff');
        $this->colorHelper->expects($this->once())->method('getTextColor')->with('#ffffff')->willReturn('#000000');

        $result = $this->getThemeSettingsHelper()->get('color.background');
        self::assertEquals('#000000', $result);
    }

    public function testGetBackgroundColorFromTheming() {
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->themingIntegration->expects($this->once())->method('getColorBackground')->willReturn('#123456');

        $result = $this->getThemeSettingsHelper()->get('color.background');
        self::assertEquals('#123456', $result);
    }

    public function testGetLabelDefault() {
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getName');

        $result = $this->getThemeSettingsHelper()->get('label');
        self::assertEquals('Nextcloud', $result);
    }

    public function testGetLabelDefaultFromOcName() {
        $this->themingDefaults = $this->createMock(OC_Defaults_With_Everything::class);
        $this->themingDefaults->expects($this->once())->method('getName')->willReturn('Label1');
        $this->themingDefaults->expects($this->never())->method('getEntity')->willReturn('Label2');

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getName');

        $result = $this->getThemeSettingsHelper()->get('label');
        self::assertEquals('Label1', $result);
    }

    public function testGetLabelDefaultFromOcEntity() {
        $this->themingDefaults = $this->createMock(OC_Defaults_With_NoName::class);
        $this->themingDefaults->expects($this->once())->method('getEntity')->willReturn('Label2');

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getName');

        $result = $this->getThemeSettingsHelper()->get('label');
        self::assertEquals('Label2', $result);
    }

    public function testGetLabelFromTheming() {
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->themingIntegration->expects($this->once())->method('getName')->willReturn('LabelTheming');

        $result = $this->getThemeSettingsHelper()->get('label');
        self::assertEquals('LabelTheming', $result);
    }

    public function testGetBackgroundImageDefault() {
        $this->unsplashIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->unsplashIntegration->expects($this->never())->method('getBackgroundImage');
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getBackgroundImage');

        $backgroundImage = 'https://example.com' . ThemeSettingsHelper::DEFAULT_BACKGROUND_PATH;
        $this->urlGenerator
            ->expects($this->once())
            ->method('getAbsoluteURL')
            ->with(ThemeSettingsHelper::DEFAULT_BACKGROUND_PATH)
            ->willReturn(
                $backgroundImage
            );

        $result = $this->getThemeSettingsHelper()->get('background');
        self::assertEquals($backgroundImage, $result);
    }

    public function testGetBackgroundImageFromTheming() {
        $backgroundImage = 'https://example.com/theming_image.png';

        $this->unsplashIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->unsplashIntegration->expects($this->never())->method('getBackgroundImage');
        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->themingIntegration->expects($this->once())->method('getBackgroundImage')->willReturn($backgroundImage);
        $this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

        $result = $this->getThemeSettingsHelper()->get('background');
        self::assertEquals($backgroundImage, $result);
    }

    public function testGetBackgroundImageFromUnsplash() {
        $backgroundImage = 'https://example.com/unsplash_image.png';

        $this->unsplashIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->unsplashIntegration->expects($this->once())->method('getBackgroundImage')->willReturn($backgroundImage);
        $this->themingIntegration->expects($this->never())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getBackgroundImage');
        $this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

        $result = $this->getThemeSettingsHelper()->get('background');
        self::assertEquals($backgroundImage, $result);
    }

    public function testGetLogoFromOcDefaults() {
        $logoUrl = 'https://example.com/logo.png';

        $this->themingDefaults = $this->createMock(OC_Defaults_With_Everything::class);
        $this->themingDefaults->expects($this->once())->method('getLogo')->willReturn('logo.png');

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getLogoIcon');

        $this->urlGenerator
            ->expects($this->once())
            ->method('getAbsoluteURL')
            ->with('logo.png')
            ->willReturn($logoUrl);

        $result = $this->getThemeSettingsHelper()->get('logo');
        self::assertEquals($logoUrl, $result);
    }

    public function testGetLogoFromTheming() {
        $logoUrl = 'https://example.com/logo.png';

        $this->themingDefaults = $this->createMock(OC_Defaults_With_Everything::class);
        $this->themingDefaults->expects($this->never())->method('getLogo');

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->themingIntegration->expects($this->once())->method('getLogoIcon')->willReturn($logoUrl);

        $this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

        $result = $this->getThemeSettingsHelper()->get('logo');
        self::assertEquals($logoUrl, $result);
    }

    public function testGetAppIconDefault() {
        $iconUrl = 'https://example.com/path/app-themed.svg';

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getAppIcon');

        $this->urlGenerator
            ->expects($this->once())
            ->method('imagePath')
            ->with(Application::APP_NAME,'app-themed.svg')
            ->willReturn('/path/app-themed.svg');

        $this->urlGenerator
            ->expects($this->once())
            ->method('getAbsoluteURL')
            ->with('/path/app-themed.svg')
            ->willReturn($iconUrl);

        $result = $this->getThemeSettingsHelper()->get('app.icon');
        self::assertEquals($iconUrl, $result);
    }

    public function testGetAppIconFromTheming() {
        $iconUrl = 'https://example.com/path/app-themed.svg';

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->themingIntegration->expects($this->once())->method('getAppIcon')->willReturn($iconUrl);

        $this->urlGenerator->expects($this->never())->method('imagePath');

        $this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

        $result = $this->getThemeSettingsHelper()->get('app.icon');
        self::assertEquals($iconUrl, $result);
    }

    public function testGetFolderIconDefault() {
        $iconUrl = 'https://example.com/path/folder-themed.svg';

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->themingIntegration->expects($this->never())->method('getFolderIcon');

        $this->urlGenerator
            ->expects($this->once())
            ->method('imagePath')
            ->with('core', 'filetypes/folder.svg')
            ->willReturn('/path/folder-themed.svg');

        $this->urlGenerator
            ->expects($this->once())
            ->method('getAbsoluteURL')
            ->with('/path/folder-themed.svg')
            ->willReturn($iconUrl);

        $result = $this->getThemeSettingsHelper()->get('folder.icon');
        self::assertEquals($iconUrl, $result);
    }

    public function testGetFolderIconFromTheming() {
        $iconUrl = 'https://example.com/path/folder-themed.svg';

        $this->themingIntegration->expects($this->once())->method('isAvailable')->willReturn(true);
        $this->themingIntegration->expects($this->once())->method('getFolderIcon')->willReturn($iconUrl);

        $this->urlGenerator->expects($this->never())->method('imagePath');
        $this->urlGenerator->expects($this->never())->method('getAbsoluteURL');

        $result = $this->getThemeSettingsHelper()->get('folder.icon');
        self::assertEquals($iconUrl, $result);
    }


    /**
     * Test if the list theme settings method works
     */
    public function testListThemeSettings() {
        $expected = [
            'server.theme.color.primary'    => '#123456',
            'server.theme.color.text'       => '#456789',
            'server.theme.color.background' => '#ffffff',
            'server.theme.background'       => 'https://cloud.com/apps/theming/img/background/background.webp',
            'server.theme.logo'             => 'https://cloud.com/core/img/logo.svg',
            'server.theme.label'            => 'Nextcloud',
            'server.theme.app.icon'         => 'https://cloud.com/apps/passwords/app-themed.svg',
            'server.theme.folder.icon'      => 'https://cloud.com/core/img/filetypes/folder.svg'
        ];


        $this->themingIntegration->method('isAvailable')->willReturn(false);
        $this->unsplashIntegration->method('isAvailable')->willReturn(false);

        $this->themingDefaults = $this->createMock(OC_Defaults_With_Everything::class);
        $this->themingDefaults->method('getColorPrimary')->willReturn('#123456');
        $this->themingDefaults->method('getTextColorPrimary')->willReturn('#456789');
        $this->themingDefaults->method('getColorBackground')->willReturn('#000000');
        $this->themingDefaults->method('getLogo')->willReturn('/core/img/logo.svg');
        $this->themingDefaults->method('getName')->willReturn('Nextcloud');

        $this->colorHelper->expects($this->once())->method('getTextColor')->with('#000000')->willReturn('#ffffff');


        $this->urlGenerator->method('imagePath')->willReturnMap(
            [
                [Application::APP_NAME, 'app-themed.svg', '/apps/passwords/app-themed.svg'],
                ['core', 'filetypes/folder.svg', '/core/img/filetypes/folder.svg']
            ]
        );

        $this->urlGenerator->method('getAbsoluteURL')->willReturnMap(
            [
                ['/core/img/logo.svg', 'https://cloud.com/core/img/logo.svg'],
                ['/apps/passwords/app-themed.svg', 'https://cloud.com/apps/passwords/app-themed.svg'],
                ['/core/img/filetypes/folder.svg', 'https://cloud.com/core/img/filetypes/folder.svg'],
                [ThemeSettingsHelper::DEFAULT_BACKGROUND_PATH, 'https://cloud.com/apps/theming/img/background/background.webp'],
            ]
        );

        $result = $this->getThemeSettingsHelper()->list();
        self::assertEquals($expected, $result);
    }

    /**
     *
     */
    protected function setUp(): void {
        $this->themingDefaults = $this->createMock(OC_Defaults::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->unsplashIntegration = $this->createMock(UnsplashIntegration::class);
        $this->themingIntegration = $this->createMock(ThemingIntegration::class);
        $this->colorHelper = $this->createMock(ThemingColorHelper::class);

        $this->getThemeSettingsHelper();
    }

    /**
     * @return void
     */
    protected function getThemeSettingsHelper(): ThemeSettingsHelper {
        return new ThemeSettingsHelper(
            $this->themingDefaults,
            $this->urlGenerator,
            $this->themingIntegration,
            $this->unsplashIntegration,
            $this->colorHelper
        );
    }
}