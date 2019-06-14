<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Cron;

use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Survey\ServerReportHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\EnvironmentService;
use OCA\Passwords\Services\LoggingService;
use OCA\Passwords\Services\NotificationService;

/**
 * Class SendServerSurvey
 *
 * @package OCA\Passwords\Cron
 */
class SendServerSurvey extends AbstractCronJob {

    const API_URL = 'https://ncpw.mdns.eu/';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var ServerReportHelper
     */
    protected $serverReport;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var NotificationService
     */
    protected $notifications;

    /**
     * SendServerSurvey constructor.
     *
     * @param LoggingService       $logger
     * @param ConfigurationService $config
     * @param RequestHelper        $requestHelper
     * @param EnvironmentService   $environment
     * @param ServerReportHelper   $serverReport
     * @param NotificationService  $notifications
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        RequestHelper $requestHelper,
        EnvironmentService $environment,
        ServerReportHelper $serverReport,
        NotificationService $notifications
    ) {
        parent::__construct($logger, $environment);
        $this->serverReport  = $serverReport;
        $this->requestHelper = $requestHelper;
        $this->config        = $config;
        $this->notifications = $notifications;
    }

    /**
     * @param $argument
     *
     * @throws \Exception
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
        $report = $this->serverReport->getReport($enhanced);
        $this->requestHelper->setJsonData($report);
        $this->requestHelper->send(self::API_URL);
    }

    /**
     * @return int
     */
    protected function getReportMode(): int {
        $mode = $this->config->getAppValue('survey/server/mode', -1);

        if($mode === -1) {
            $this->sendNotifications();

            return 0;
        }

        return intval($mode);
    }

    /**
     *
     */
    protected function sendNotifications(): void {
        $time = $this->config->getAppValue('survey/server/notification', 0);
        if($time > strtotime('-3 months')) return;

        $adminGroup = \OC::$server->getGroupManager()->get('admin');
        foreach($adminGroup->getUsers() as $admin) {
            $this->notifications->sendSurveyNotification($admin->getUID());
        }
        $this->config->setAppValue('survey/server/notification', time());
    }
}