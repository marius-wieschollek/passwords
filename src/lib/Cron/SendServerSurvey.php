<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use Exception;
use OCA\Passwords\Helper\Survey\ServerReportHelper;
use OCA\Passwords\Helper\User\AdminUserHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\NotificationService;

/**
 * Class SendServerSurvey
 *
 * @package OCA\Passwords\Cron
 */
class SendServerSurvey extends AbstractTimedJob {

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var AdminUserHelper
     */
    protected AdminUserHelper $adminHelper;

    /**
     * @var ServerReportHelper
     */
    protected ServerReportHelper $serverReport;

    /**
     * @var NotificationService
     */
    protected NotificationService $notifications;

    /**
     * @var float|int
     */
    protected $interval = 259200;

    /**
     * SendServerSurvey constructor.
     *
     * @param LoggingService       $logger
     * @param ConfigurationService $config
     * @param AdminUserHelper      $adminHelper
     * @param EnvironmentService   $environment
     * @param ServerReportHelper   $serverReport
     * @param NotificationService  $notifications
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        AdminUserHelper $adminHelper,
        EnvironmentService $environment,
        ServerReportHelper $serverReport,
        NotificationService $notifications
    ) {
        parent::__construct($logger, $config, $environment);
        $this->serverReport  = $serverReport;
        $this->config        = $config;
        $this->adminHelper   = $adminHelper;
        $this->notifications = $notifications;
    }

    /**
     * @param $argument
     *
     * @throws Exception
     */
    protected function runJob($argument): void {
        $mode = $this->getReportMode();

        if($mode !== 0) $this->sendReport($mode > 1);
    }

    /**
     * @param bool $enhanced
     *
     * @return void
     */
    protected function sendReport(bool $enhanced): void {
        $this->serverReport->sendReport($enhanced);
    }

    /**
     * @return int
     */
    protected function getReportMode(): int {
        $mode = intval($this->config->getAppValue('survey/server/mode', -1));
        if($mode === -1) {
            if($this->serverReport->hasData()) $this->sendNotifications();

            return 0;
        }

        return $mode;
    }

    /**
     *
     */
    protected function sendNotifications(): void {
        $time = intval($this->config->getAppValue('survey/server/notification', 0));
        if($time > strtotime('-6 months')) return;

        foreach($this->adminHelper->getAdmins() as $admin) {
            $this->notifications->sendSurveyNotification($admin->getUID());
        }
        $this->config->setAppValue('survey/server/notification', time());
    }
}