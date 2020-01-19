<?php

namespace OCA\Passwords\Helper\Settings;

use OCA\Passwords\Services\ConfigurationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var \OCP\IURLGenerator
     */
    protected $urlGenerator;

    /**
     *
     */
    protected function setUp(): void {
        $this->configurationService = $this->createMock(ConfigurationService::class);
        $this->themingDefaults = $this->createMock(\OC_Defaults::class);
        $this->urlGenerator = $this->createMock(\OCP\IURLGenerator::class);
        $this->themeSettingsHelper   = new ThemeSettingsHelper($this->configurationService, $this->themingDefaults, $this->urlGenerator);
    }

    /**
     * Test if default text color is returned correctly
     *
     * @throws \Exception
     */
    public function testGetTextColor() {
        $this->themingDefaults->method('getTextColorPrimary')->willReturn('#000000');
        $this->themingDefaults->expects($this->once())->method('getTextColorPrimary');

        $result = $this->themeSettingsHelper->get('color.text');
        self::assertEquals('#000000', $result);
    }

    /**
     * Test if default primary color is returned correctly
     *
     * @throws \Exception
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
     *
     * @throws \Exception
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
     *
     * @throws \Exception
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
     *
     * @throws \Exception
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
     * Test if breezedark primary color is returned correctly when the app is enabled
     *
     * @throws \Exception
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
     *
     * @throws \Exception
     */
    public function testGetLabel() {
        $this->themingDefaults->method('getEntity')->willReturn('Nextcloud');
        $this->themingDefaults->expects($this->once())->method('getEntity');

        $result = $this->themeSettingsHelper->get('label');
        self::assertEquals('Nextcloud', $result);
    }
}