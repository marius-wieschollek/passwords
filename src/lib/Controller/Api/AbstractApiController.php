<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 22:12
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\AbstractObjectHelper;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class AbstractApiController
 *
 * @package OCA\Passwords\Controller
 */
abstract class AbstractApiController extends ApiController {

    public function __construct(IRequest $request) {
        parent::__construct(
            Application::APP_NAME,
            $request,
            'PUT, POST, GET, DELETE, PATCH',
            'Authorization, Content-Type, Accept',
            1728000
        );
    }

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated'];

    /**
     * @param     $response
     * @param int $statusCode
     *
     * @return JSONResponse
     */
    protected function createJsonResponse($response, int $statusCode = Http::STATUS_OK): JSONResponse {
        return new JSONResponse(
            $response, $statusCode
        );
    }

    /**
     * @param \Throwable $e
     *
     * @return JSONResponse
     */
    protected function createErrorResponse(\Throwable $e): JSONResponse {
        $message    = "Unable to complete request";
        $id         = 0;
        $statusCode = Http::STATUS_SERVICE_UNAVAILABLE;

        \OC::$server->getLogger()->logException($e, ['app' => $this->appName]);

        if(get_class($e) === ApiException::class || is_subclass_of($e, ApiException::class)) {
            /** @var ApiException $e */
            $id         = $e->getId();
            $message    = $e->getMessage();
            $statusCode = $e->getHttpCode();
        }

        if(get_class($e) === DoesNotExistException::class) {
            $id         = 404;
            $message    = 'Resource not found';
            $statusCode = 404;
        }

        $response = new JSONResponse(
            [
                'status'  => 'error',
                'id'      => $id,
                'message' => $message
            ], $statusCode
        );

        return $response;
    }

    /**
     * @throws ApiException
     */
    protected function checkAccessPermissions() {
        if(!$this->checkIfHttpsUsed()) {
            throw new ApiException('HTTPS required', 400);
        }
    }

    /**
     * @param array $criteria
     *
     * @return array
     * @throws ApiException
     */
    protected function processSearchCriteria($criteria = []): array {
        $filters = [];
        foreach($criteria as $key => $value) {
            if(!in_array($key, $this->allowedFilterFields)) {
                throw new ApiException('Illegal field '.$key, 400);
            }

            if($value === 'true') {
                $value = true;
            } else if($value === 'false') {
                $value = false;
            } else if(is_array($value) && !in_array($value[0], AbstractObjectHelper::$filterOperators)) {
                throw new ApiException('Illegal operator '.$value[0], 400);
            }

            $filters[ $key ] = $value;
        }

        return $filters;
    }

    /**
     * @return bool
     */
    protected function checkIfHttpsUsed(): bool {
        $config      = \OC::$server->getConfig();
        $forceSsl    = $config->getSystemValue('forcessl', false);
        $protocol    = $config->getSystemValue('overwriteprotocol', '');
        $ignoreHttps = $config->getAppValue('passwords', 'environment', 'production') === 'dev';

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] === 443 || $protocol === 'https' || $forceSsl || $ignoreHttps;
    }
}