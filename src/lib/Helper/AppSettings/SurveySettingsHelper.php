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
 * Class SurveySettingsHelper
 *
 * @package OCA\Passwords\Helper\AppSettings
 */
class SurveySettingsHelper extends AbstractSettingsHelper {

    /**
     * @var IL10N
     */
    protected $localisation;

    /**
     * @var
     */
    protected $scope = 'survey';

    /**
     * @var array
     */
    protected $keys = ['server' => 'survey/server/mode'];

    /**
     * @var array
     */
    protected $types = ['server' => 'select:number'];

    /**
     * @var array
     */
    protected $defaults = ['server' => 0];

    /**
     * ServiceSettingsHelper constructor.
     *
     * @param ConfigurationService $config
     * @param IL10N                $localisation
     */
    public function __construct(ConfigurationService $config, IL10N $localisation) {
        parent::__construct($config);
        $this->localisation = $localisation;
    }

    /**
     * @return array
     */
    protected function getServerOptions(): array {
        $options = [
            $this->generateOptionArray(
                0,
                $this->localisation->t('None')
            ),
            $this->generateOptionArray(
                1,
                $this->localisation->t('Basic')
            ),
            $this->generateOptionArray(
                2,
                $this->localisation->t('Full')
            ),
        ];

        if(!$this->config->hasAppValue('survey/server/mode')) {
            array_unshift(
                $options,
                $this->generateOptionArray(
                    -1,
                    $this->localisation->t('Not set')
                )
            );
        }

        return $options;
    }
}