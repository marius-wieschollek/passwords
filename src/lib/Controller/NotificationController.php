<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Survey\ServerReportHelper;
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
     * @var ServerReportHelper
     */
    protected $serverReport;

    /**
     * NotificationController constructor.
     *
     * @param string               $appName
     * @param IRequest             $request
     * @param IManager             $notifications
     * @param ConfigurationService $config
     * @param EnvironmentService   $environment
     * @param ServerReportHelper   $serverReport
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IManager $notifications,
        ConfigurationService $config,
        EnvironmentService $environment,
        ServerReportHelper $serverReport
    ) {
        parent::__construct($appName, $request);
        $this->config        = $config;
        $this->environment   = $environment;
        $this->notifications = $notifications;
        $this->serverReport = $serverReport;
    }

    /**
     * @param string $answer
     */
    public function survey(string $answer = 'yes'): void {
        if($answer === 'yes') {
            $this->config->setAppValue('survey/server/mode', 2);
            $this->removeNotification(true);
            $this->serverReport->sendReport();
        } else {
            $this->removeNotification(false);
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