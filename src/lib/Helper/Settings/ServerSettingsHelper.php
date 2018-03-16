<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Settings;

use OCA\Passwords\Exception\ApiException;
use OCP\IURLGenerator;

/**
 * Class ServerSettingsHelper
 *
 * @package OCA\Passwords\Helper\Settings
 */
class ServerSettingsHelper {

    /**
     * @var IURLGenerator
     */
    protected $urlGenerator;

    /**
     * @var ShareSettingsHelper
     */
    protected $shareSettings;

    /**
     * @var ThemeSettingsHelper
     */
    protected $themeSettings;

    /**
     * ServerSettingsHelper constructor.
     *
     * @param ShareSettingsHelper $shareSettings
     * @param ThemeSettingsHelper $themeSettings
     * @param IURLGenerator       $urlGenerator
     */
    public function __construct(
        IURLGenerator $urlGenerator,
        ShareSettingsHelper $shareSettings,
        ThemeSettingsHelper $themeSettings
    ) {
        $this->urlGenerator  = $urlGenerator;
        $this->shareSettings = $shareSettings;
        $this->themeSettings = $themeSettings;
    }

    /**
     * @param string $key
     *
     * @return null|string
     * @throws ApiException
     */
    public function get(string $key) {
        list($scope, $subKey) = explode('.', $key, 2);

        switch($scope) {
            case 'baseUrl':
                return $this->urlGenerator->getBaseUrl();
            case 'theme':
                return $this->themeSettings->get($subKey);
            case 'sharing':
                return $this->shareSettings->get($subKey);
        }

        throw new ApiException('Invalid Key', 400);
    }

    /**
     * @return array
     * @throws ApiException
     */
    public function list(): array {
        return array_merge(
            ['server.baseUrl' => $this->get('baseUrl')],
            $this->themeSettings->list(),
            $this->shareSettings->list()
        );
    }
}