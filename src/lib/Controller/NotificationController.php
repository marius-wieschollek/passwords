<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\Services\ConfigurationService;
use OCP\IRequest;

/**
 * Class NotificationController
 *
 * @package OCA\Passwords\Controller
 */
class NotificationController extends \OCP\AppFramework\Controller{

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * NotificationController constructor.
     *
     * @param string               $appName
     * @param IRequest             $request
     * @param ConfigurationService $config
     */
    public function __construct(string $appName, IRequest $request, ConfigurationService $config) {
        parent::__construct($appName, $request);
        $this->config = $config;
    }

    /**
     * @param string $answer
     */
    public function survey(string $answer = 'yes'): void {
        $mode = $this->config->getAppValue('survey/server/mode', -1);

        if($mode < 1) $this->config->setAppValue('survey/server/mode', $answer === 'no' ? 0:2);
    }
}