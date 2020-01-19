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

        $result = $this->themeSettingsHelper->get('text.color');
        self::assertEquals('#000000', $result);
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