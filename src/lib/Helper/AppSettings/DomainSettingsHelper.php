<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\AppSettings;

use OCA\Passwords\Services\ConfigurationService;
use OCP\IL10N;

/**
 * Class DomainSettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class DomainSettingsHelper extends AbstractSettingsHelper {

        /**
     * @var IL10N
     */
    protected IL10N $localisation;

    /**
     * @var string
     */
    protected string $scope = 'domain';

    /**
     * @var array
     */
    protected array $keys
        = [
            'mapping.default.enabled' => 'domain/mapping/default/enabled',
            'mapping.custom'          => 'domain/mapping/custom'
        ];

    /**
     * @var array
     */
    protected array $types
        = [
            'mapping.default.enabled' => 'boolean',
            'mapping.custom'          => 'string'
        ];

    /**
     * @var array
     */
    protected array $defaults
        = [
            'mapping.default.enabled' => false,
            'mapping.custom'          => "{\"data\":[]}"
        ];

}