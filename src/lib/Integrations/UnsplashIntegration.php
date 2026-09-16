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

use OCA\Passwords\Services\ConfigurationService;
use OCA\Unsplash\ProviderHandler\Provider;
use OCA\Unsplash\Services\SettingsService;

class UnsplashIntegration {

    /**
     * @param ConfigurationService $config
     */
    public function __construct(protected ConfigurationService $config) {
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        return $this->config->isAppEnabled('unsplash') && class_exists(\OCA\Unsplash\Services\SettingsService::class);
    }

    /**
     * @return string|null
     */
    public function getBackgroundImage(): ?string {
        try {
            $settings = \OC::$server->get(SettingsService::class);

            return $settings->headerbackgroundLink(Provider::SIZE_NORMAL);
        } catch (\Throwable $e) {
            return null;
        }
    }
}