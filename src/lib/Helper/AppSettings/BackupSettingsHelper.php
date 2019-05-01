<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\AppSettings;

/**
 * Class BackupSettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class BackupSettingsHelper extends AbstractSettingsHelper {

    /**
     * @var
     */
    protected $scope = 'backup';

    /**
     * @var array
     */
    protected $keys
        = [
            'interval'  => 'backup/interval',
            'files.max' => 'backup/files/maximum'
        ];

    /**
     * @var array
     */
    protected $types
        = [
            'interval'  => 'select:number',
            'files.max' => 'number'
        ];

    /**
     * @var array
     */
    protected $defaults
        = [
            'interval'  => 86400,
            'files.max' => 14
        ];

    /**
     * @return array
     */
    protected function getIntervalOptions(): array {
        return [
            $this->generateOptionArray(
                3600,
                'Every hour'
            ),
            $this->generateOptionArray(
                21600,
                'Every six hours'
            ),
            $this->generateOptionArray(
                86400,
                'Every day'
            ),
            $this->generateOptionArray(
                172800,
                'Every two days'
            ),
            $this->generateOptionArray(
                604800,
                'Every week'
            ),
            $this->generateOptionArray(
                1209600,
                'Every two weeks'
            ),
        ];
    }
}