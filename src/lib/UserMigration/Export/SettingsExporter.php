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

namespace OCA\Passwords\UserMigration\Export;

use OCA\Passwords\Helper\Settings\UserSettingsHelper;
use OCA\Passwords\Services\ConfigurationService;

class SettingsExporter {

    public function __construct(protected UserSettingsHelper $userSettingsHelper, protected ConfigurationService $configurationService) {
    }

    public function exportData(string $userId) {
        return [
            'settings' => [
                'user'   => $this->userSettingsHelper->listRaw($userId),
                'client' => $this->configurationService->getUserValue('client/settings', '{}', $userId)
            ]
        ];
    }
}