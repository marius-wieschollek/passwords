<?php

namespace OCA\Passwords\Integrations;

use OC;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Theming\Capabilities;
use OCA\Theming\ThemingDefaults;
use OCP\IURLGenerator;

class ThemingIntegration {

    protected ?array $capabilities = null;

    public function __construct(
        protected ConfigurationService $config,
        protected IURLGenerator        $urlGenerator
    ) {
    }

    public function isAvailable(): bool {
        return $this->config->isAppEnabled('theming') && class_exists(ThemingDefaults::class);
    }

    public function getFolderIcon(): string {
        return $this->urlGenerator->linkToRouteAbsolute(
            'theming.Icon.getThemedIcon',
            [
                'app'   => 'core',
                'image' => 'filetypes/folder.svg',
                'v'     => $this->getCapability('cacheBuster')
            ]
        );
    }

    public function getAppIcon(): string {
        return $this->urlGenerator->linkToRouteAbsolute(
            'theming.Icon.getThemedIcon',
            [
                'app'   => Application::APP_NAME,
                'image' => 'app-themed.svg',
                'v'     => $this->getCapability('cacheBuster')
            ]
        );
    }

    public function getLogoIcon(): string {
        $logoUrl = $this->getCapability('logoUrl');

        return $logoUrl ?? $this->getThemingDefaults()->getLogo();
    }

    public function getName(): string {
        $color = $this->getCapability('name');

        return $color ?? $this->getThemingDefaults()->getName();
    }

    public function getTextColorPrimary(): string {
        $color = $this->getCapability('color-text');

        return $color ?? $this->getThemingDefaults()->getTextColorPrimary();
    }

    public function getColorPrimary(): string {
        $color = $this->getCapability('color');

        return $color ?? $this->getThemingDefaults()->getColorPrimary();
    }

    public function getColorBackground(): string {
        $backgroundColor = $this->getCapability('backgroundColor');

        return $backgroundColor ?? $this->getThemingDefaults()->getColorBackground();
    }

    /**
     * @return string
     */
    public function getBackgroundImage(): string {
        $userBackground = $this->getCapability('background');

        return $userBackground ?? $this->getThemingDefaults()->getBackground();
    }


    protected function getThemingDefaults() {
        return \OC::$server->get(ThemingDefaults::class);
    }

    /**
     * @param $capability
     * @return array|mixed|null
     */
    protected function getCapability(string $capability) {
        if (!class_exists(Capabilities::class)) {
            return null;
        }


        if (!$this->capabilities) {
            $capabilities = OC::$server->get(Capabilities::class);

            $this->capabilities = $capabilities->getCapabilities();
        }

        return $this->capabilities[$capability] ?? null;
    }
}