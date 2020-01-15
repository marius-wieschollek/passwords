<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\Cron\SynchronizeShares;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class CronController
 *
 * @package OCA\Passwords\Controller
 */
class CronController extends Controller {

    /**
     * @var SynchronizeShares
     */
    protected $synchronizeShares;

    /**
     * CronController constructor.
     *
     * @param string            $appName
     * @param IRequest          $request
     * @param SynchronizeShares $synchronizeShares
     */
    public function __construct(string $appName, IRequest $request, SynchronizeShares $synchronizeShares) {
        parent::__construct($appName, $request);
        $this->synchronizeShares = $synchronizeShares;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @UserRateThrottle(limit=3, period=10)
     *
     * @param string $job
     *
     * @return JSONResponse
     */
    public function execute(string $job): JSONResponse {

        if($job === 'sharing') {
            return new JSONResponse(['success' => $this->synchronizeShares->runManually()]);
        }

        return new JSONResponse(['success' => false], Http::STATUS_NOT_FOUND);
    }
}