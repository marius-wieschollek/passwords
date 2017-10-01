<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 30.09.17
 * Time: 21:24
 */

namespace OCA\Passwords\Cron;

use OC\BackgroundJob\TimedJob;
use OCA\Passwords\Helper\CommonPasswordsDownloadHelper;
use OCA\Passwords\Helper\SecurityCheck\LocalSecurityCheckHelper;
use OCA\Passwords\Services\HelperService;

/**
 * Class CheckPasswordsJob
 *
 * @package OCA\Passwords\Cron
 */
class CheckPasswordsJob extends TimedJob {

    /**
     * @var HelperService
     */
    protected $helperService;

    /**
     * @var CommonPasswordsDownloadHelper
     */
    protected $localPasswordListHelper;

    /**
     * CheckPasswordsJob constructor.
     *
     * @param HelperService                 $helperService
     * @param CommonPasswordsDownloadHelper $localPasswordListHelper
     */
    public function __construct(
        HelperService $helperService,
        CommonPasswordsDownloadHelper $localPasswordListHelper
    ) {
        // Run once per day
        //$this->setInterval(24 * 60 * 60);
        $this->setInterval(15 * 60);
        $this->helperService           = $helperService;
        $this->localPasswordListHelper = $localPasswordListHelper;
    }

    /**
     * @param $argument
     */
    protected function run($argument) {
        $passwordCheckHelper = $this->helperService->getSecurityHelper();

        if(get_class($passwordCheckHelper) == LocalSecurityCheckHelper::class &&
           $this->localPasswordListHelper->isUpdateRequired()) {
            $this->localPasswordListHelper->update();
        }
    }
}