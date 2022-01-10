<?php

namespace OCA\Passwords\Controller;

use DateTime;
use Exception;
use OC\App\AppManager;
use DateTimeInterface;
use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\LoggingService;
use OCP\App\AppPathNotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

/**
 * Class LanguageController
 *
 * @package OCA\Passwords\Controller
 */
class LanguageController extends Controller {

    /**
     * @var AppManager
     */
    protected AppManager $appManager;

    /**
     * @var LoggingService
     */
    protected LoggingService $logger;

    /**
     * LanguageController constructor.
     *
     * @param                $appName
     * @param IRequest       $request
     * @param AppManager     $appManager
     * @param LoggingService $logger
     */
    public function __construct($appName, IRequest $request, AppManager $appManager, LoggingService $logger) {
        parent::__construct($appName, $request);
        $this->appManager = $appManager;
        $this->logger     = $logger;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $section
     * @param string $language
     *
     * @return Response
     */
    public function getFile(string $section, string $language): Response {
        if(!in_array($section, ['apps', 'backups', 'settings', 'tutorial'])) {
            return new NotFoundResponse();
        }

        if(preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $language) !== 1) {
            return new NotFoundResponse();
        }

        try {
            $json = $this->readLanguageFile($section, $language);

            if($json !== null) return $this->createJsonResponse($json);
        } catch(Exception $e) {
            $this->logger->logException($e);
        }

        return new NotFoundResponse();
    }

    /**
     * @param string $section
     * @param string $language
     *
     * @return object|null
     * @throws AppPathNotFoundException
     */
    protected function readLanguageFile(string $section, string $language): ?object {
        $basePath  = $this->appManager->getAppPath(Application::APP_NAME).DIRECTORY_SEPARATOR.'l10n';
        $filePath = $basePath.DIRECTORY_SEPARATOR.
                    $section.DIRECTORY_SEPARATOR.
                    $language.'.json';

        if(is_file($filePath) && str_starts_with($filePath, $basePath)) {
            $data = file_get_contents($filePath);
            $json = json_decode($data);

            return is_object($json) ? $json:null;
        }

        return null;
    }

    /**
     * @param object $json
     * @param int    $statusCode
     *
     * @return JSONResponse
     * @throws Exception
     */
    protected function createJsonResponse(object $json, int $statusCode = Http::STATUS_OK): JSONResponse {
        $response = new JSONResponse(
            $json,
            $statusCode
        );

        $expires = new DateTime('@'.(time() + 2419200));
        $response->addHeader('Cache-Control', 'public, immutable, max-age=2419200')
                 ->addHeader('Expires', $expires->format(DateTimeInterface::RFC2822))
                 ->addHeader('Pragma', 'cache');

        return $response;
    }
}