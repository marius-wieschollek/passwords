<?php

namespace OCA\Passwords\Controller;

use OCA\Passwords\Cron\SynchronizeShares;
use OCP\AppFramework\Controller;
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
     */
    public function run(): JSONResponse {
        return new JSONResponse(['success' => $this->synchronizeShares->runManually()]);
    }
}