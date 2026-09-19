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

namespace OCA\Passwords\Helper\Settings;

use OC_Defaults;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Theming\ThemingColorHelper;
use OCA\Passwords\Integrations\ThemingIntegration;
use OCA\Passwords\Integrations\UnsplashIntegration;
use OCP\IURLGenerator;

/**
 * Class ThemeSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ThemeSettingsHelper {
    const string DEFAULT_BACKGROUND_PATH = '/apps/theming/img/background/jo-myoung-hee-fluid.webp';

    /**
     * ThemeSettingsHelper constructor.
     *
     * @param OC_Defaults         $theming
     * @param IURLGenerator       $urlGenerator
     * @param ThemingIntegration  $themingIntegration
     * @param UnsplashIntegration $unsplashIntegration
     * @param ThemingColorHelper  $colorHelper
     */
    public function __construct(
        protected OC_Defaults          $theming,
        protected IURLGenerator        $urlGenerator,
        protected ThemingIntegration   $themingIntegration,
        protected UnsplashIntegration  $unsplashIntegration,
        protected ThemingColorHelper   $colorHelper,
    ) {
    }

    /**
     * @param string $key
     *
     * @return null|string
     */
    public function get(string $key) {
        switch ($key) {
            case 'color':
            case 'color.primary':
                return $this->getColorPrimary();
            case 'color.text':
            case 'text.color':
                return $this->getTextColor();
            case 'color.background':
                return $this->getBackgroundColor();
            case 'background':
                return $this->getBackgroundImage();
            case 'logo':
                return $this->getLogoIcon();
            case 'label':
                return $this->getName();
            case 'app.icon':
                return $this->getAppIcon();
            case 'folder.icon':
                return $this->getFolderIcon();
        }

        return null;
    }

    /**
     * @return array
     */
    public function list(): array {
        return [
            'server.theme.color.primary'    => $this->getColorPrimary(),
            'server.theme.color.text'       => $this->getTextColor(),
            'server.theme.color.background' => $this->getBackgroundColor(),
            'server.theme.background'       => $this->getBackgroundImage(),
            'server.theme.logo'             => $this->getLogoIcon(),
            'server.theme.label'            => $this->getName(),
            'server.theme.app.icon'         => $this->getAppIcon(),
            'server.theme.folder.icon'      => $this->getFolderIcon()
        ];
    }

    /**
     * @return string
     */
    public function getLogoIcon(): string {
        if ($this->themingIntegration->isAvailable()) {
            return $this->themingIntegration->getLogoIcon();
        }

        return $this->urlGenerator->getAbsoluteURL($this->theming->getLogo());
    }

    /**
     * @return string
     */
    protected function getFolderIcon(): string {
        if ($this->themingIntegration->isAvailable()) {
            return $this->themingIntegration->getFolderIcon();

        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath('core', 'filetypes/folder.svg')
        );
    }

    /**
     * @return string
     */
    protected function getAppIcon(): string {
        if ($this->themingIntegration->isAvailable()) {
            return $this->themingIntegration->getAppIcon();
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_NAME, 'app-themed.svg')
        );
    }

    /**
     * @return string
     */
    protected function getBackgroundImage(): string {
        if ($this->unsplashIntegration->isAvailable()) {
            $background = $this->unsplashIntegration->getBackgroundImage();

            if ($background) return $background;
        }

        if ($this->themingIntegration->isAvailable()) {
            return $this->themingIntegration->getBackgroundImage();
        }

        return $this->urlGenerator->getAbsoluteURL(self::DEFAULT_BACKGROUND_PATH);
    }

    /**
     * @return string
     */
    protected function getBackgroundColor(): string {
        if ($this->themingIntegration->isAvailable()) {
            return $this->themingIntegration->getColorBackground();
        }

        if (method_exists($this->theming, 'getColorBackground')) {
            return $this->colorHelper->getTextColor($this->theming->getColorBackground());
        }

        return '#ffffff';
    }

    /**
     * @return string
     */
    protected function getColorPrimary(): string {
        if ($this->themingIntegration->isAvailable()) {
            return $this->themingIntegration->getColorPrimary();
        }

        if (method_exists($this->theming, 'getColorPrimary')) {
            return $this->theming->getColorPrimary();
        }

        return '#00679e';
    }

    /**
     * @return string
     */
    protected function getTextColor(): string {
        if ($this->themingIntegration->isAvailable()) {
            return $this->themingIntegration->getTextColorPrimary();
        }

        if (method_exists($this->theming, 'getTextColorPrimary')) {
            return $this->theming->getTextColorPrimary();
        }

        return '#000000';
    }

    protected function getName(): string {
        if ($this->themingIntegration->isAvailable()) {
            return $this->themingIntegration->getName();
        }

        if (method_exists($this->theming, 'getName')) {
            return $this->theming->getName();
        }

        if (method_exists($this->theming, 'getEntity')) {
            return $this->theming->getEntity();
        }

        return 'Nextcloud';
    }
}