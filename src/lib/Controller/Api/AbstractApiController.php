<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 22:12
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiAccessDeniedException;
use OCA\Passwords\Exception\ApiException;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;

/**
 * Class AbstractApiController
 *
 * @package OCA\Passwords\Controller
 */
abstract class AbstractApiController extends ApiController {

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
            $id = 404;
            $message = 'Resource not found';
            $statusCode = 404;
        }

        $response = new JSONResponse(
            [
                'status'  => 'error',
                'id'      => $id,
                'message' => $message
            ], $statusCode
        );

        if(get_class($e) === ApiAccessDeniedException::class) {
            $headers = $response->getHeaders();
            $headers['WWW-Authenticate'] = 'basic + token';
            $response->setHeaders($headers);
        }

        return $response;
    }

    /**
     * @throws ApiAccessDeniedException
     */
    protected function checkAccessPermissions() {
        $token = $this->request->getHeader('X-Passwords-Token');
        if(empty($token)) {
            throw new ApiAccessDeniedException();
        }
    }
}