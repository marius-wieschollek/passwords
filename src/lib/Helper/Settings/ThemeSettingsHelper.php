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

/**
 * Class ThemeSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ThemeSettingsHelper {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var OC_Defaults
     */
    protected $theming;

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * ThemeSettingsHelper constructor.
     *
     * @param ConfigurationService $config
     * @param OC_Defaults          $theming
     * @param IURLGenerator        $urlGenerator
     */
    public function __construct(ConfigurationService $config, OC_Defaults $theming, IURLGenerator $urlGenerator) {
        $this->config       = $config;
        $this->theming      = $theming;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param string $key
     *
     * @return null|string
     */
    public function get(string $key) {
        switch($key) {
            case 'color':
            case 'color.primary':
                return $this->getColorPrimary();
            case 'color.text':
            case 'text.color':
                return $this->theming->getTextColorPrimary();
            case 'color.background':
                return $this->getBackgroundColor();
            case 'background':
                return $this->getBackgroundImage();
            case 'logo':
                return $this->urlGenerator->getAbsoluteURL($this->theming->getLogo());
            case 'label':
                return $this->theming->getEntity();
            case 'app.icon':
                return $this->getThemedAppIcon();
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
            'server.theme.color.text'       => $this->get('color.text'),
            'server.theme.color.background' => $this->getBackgroundColor(),
            'server.theme.background'       => $this->getBackgroundImage(),
            'server.theme.logo'             => $this->get('logo'),
            'server.theme.label'            => $this->get('label'),
            'server.theme.app.icon'         => $this->getThemedAppIcon(),
            'server.theme.folder.icon'      => $this->getFolderIcon()
        ];
    }

    /**
     * @return string
     */
    protected function getFolderIcon(): string {
        if($this->config->isAppEnabled('theming')) {
            $version = $this->config->getAppValue('cachebuster', '0', 'theming');

            return $this->urlGenerator->linkToRouteAbsolute('theming.Icon.getThemedIcon', ['app' => 'core', 'image' => 'filetypes/folder.svg', 'v' => $version]);
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath('core', 'filetypes/folder.svg')
        );
    }

    /**
     * @return string
     */
    protected function getThemedAppIcon(): string {
        if($this->config->isAppEnabled('theming')) {
            $version = $this->config->getAppValue('cachebuster', '0', 'theming');

            return $this->urlGenerator->linkToRouteAbsolute('theming.Icon.getThemedIcon', ['app' => Application::APP_NAME, 'image' => 'app-themed.svg', 'v' => $version]);
        }

        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->imagePath(Application::APP_NAME, 'app-themed.svg')
        );
    }

    /**
     * @return string
     */
    protected function getBackgroundImage(): string {
        if(method_exists($this->theming, 'getBackground')) {
            $url = $this->theming->getBackground();
        } else {
            $url = $this->urlGenerator->imagePath('core', 'background.png');
        }
        if($this->config->isAppEnabled('unsplash')) {
            return 'https://source.unsplash.com/random/featured/?nature';
        }

        return $this->urlGenerator->getAbsoluteURL($url);
    }

    /**
     * @return string
     */
    protected function getBackgroundColor(): string {
        try {
            if(in_array($this->config->getUserValue('theme', 'none', null, 'accessibility'), ['themedark', 'dark'])) {
                return '#181818';
            }
        } catch(\Throwable $e) {

        }

        if($this->config->isAppEnabled('breezedark')) {
            return '#31363b';
        }

        return '#ffffff';
    }

    /**
     * @return string
     */
    protected function getColorPrimary(): string {
        if($this->config->isAppEnabled('breezedark')) {
            return '#3daee9';
        }

        return $this->theming->getColorPrimary();
    }
}