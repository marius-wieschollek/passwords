<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Helper\AppSettings;

/**
 * Class EntitySettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class EntitySettingsHelper extends AbstractSettingsHelper {

    /**
     * @var string
     */
    protected string $scope = 'entity';

    /**
     * @var array
     */
    protected array $keys
        = [
            'purge.timeout' => 'entity/purge/timeout'
        ];

    /**
     * @var array
     */
    protected array $types
        = [
            'purge.timeout' => 'select:number'
        ];

    /**
     * @var array
     */
    protected array $defaults
        = [
            'purge.timeout' => -1
        ];

    /**
     * @return array
     * @noinspection PhpUnused
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