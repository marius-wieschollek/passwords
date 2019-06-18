<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCP\IRequest;
use OCP\Notification\IManager;

/**
 * Class NotificationController
 *
 * @package OCA\Passwords\Controller
 */
class NotificationController extends \OCP\AppFramework\Controller {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var EnvironmentService
     */
    protected $environment;

    /**
     * @var IManager
     */
    protected $notifications;

    /**
     * NotificationController constructor.
     *
     * @param string               $appName
     * @param IRequest             $request
     * @param ConfigurationService $config
     * @param IManager             $notifications
     * @param EnvironmentService   $environment
     */
    public function __construct(string $appName, IRequest $request, ConfigurationService $config, IManager $notifications, EnvironmentService $environment) {
        parent::__construct($appName, $request);
        $this->config        = $config;
        $this->environment   = $environment;
        $this->notifications = $notifications;
    }

    /**
     * @param string $answer
     */
    public function survey(string $answer = 'yes'): void {
        $mode = $this->config->getAppValue('survey/server/mode', -1);

        if($mode < 1) {
            $this->config->setAppValue('survey/server/mode', $answer === 'no' ? 0:2);
            $this->removeNotification($answer !== 'no');
        }
    }

    /**
     * @param bool $allUsers
     */
    protected function removeNotification(bool $allUsers): void {
        $notification = $this->notifications->createNotification();
        $notification->setApp(Application::APP_NAME)
                     ->setObject('admin', 'survey');
        if(!$allUsers) $notification->setUser($this->environment->getUserId());

        $this->notifications->markProcessed($notification);
    }
}