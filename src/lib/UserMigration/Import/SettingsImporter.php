<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\UserMigration\Import;

use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;

class SettingsImporter {

    public function __construct(protected UserSettingsHelper $userSettingsHelper, protected ConfigurationService $configurationService) {
    }

    public function importData(string $userId, array $data): void {
        $this->importUserSettings($userId, $data['settings']['user']);
        $this->configurationService->setUserValue('client/settings', $data['settings']['client'], $userId);
    }

    protected function importUserSettings(string $userId, array $settings): void {

        $keys = array_keys($this->userSettingsHelper->listRaw($userId));
        foreach($keys as $key) {
            if(isset($settings[ $key ]) && $settings[ $key ] !== null) {
                $this->userSettingsHelper->set($key, $settings[ $key ], $userId);
            } else {
                $this->userSettingsHelper->reset($key, $userId);
            }
        }
    }
}