<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\AppSettings;

/**
 * Class EntitySettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class EntitySettingsHelper extends AbstractSettingsHelper {

    /**
     * @var
     */
    protected $scope = 'entity';

    /**
     * @var array
     */
    protected $keys
        = [
            'purge.timeout' => 'entity/purge/timeout'
        ];

    /**
     * @var array
     */
    protected $types
        = [
            'purge.timeout' => 'select:number'
        ];

    /**
     * @var array
     */
    protected $defaults
        = [
            'purge.timeout' => -1
        ];

    /**
     * @return array
     */
    protected function getPurgeTimeoutOptions(): array {
        return [
            $this->generateOptionArray(
                -1,
                'Never'
            ),
            $this->generateOptionArray(
                0,
                'Immediately'
            ),
            $this->generateOptionArray(
                7200,
                'After two hours'
            ),
            $this->generateOptionArray(
                86400,
                'After one day'
            ),
            $this->generateOptionArray(
                1209600,
                'After two weeks'
            ),
            $this->generateOptionArray(
                2592000,
                'After one month'
            ),
            $this->generateOptionArray(
                31536000,
                'After one year'
            ),
        ];
    }
}