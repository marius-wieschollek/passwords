<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 22:12
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCP\AppFramework\ApiController;
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

        \OC::$server->getLogger()->logException($e);

        if(get_class($e) === ApiException::class || is_subclass_of($e, ApiException::class)) {
            /** @var ApiException $e */
            $id         = $e->getId();
            $message    = $e->getMessage();
            $statusCode = $e->getHttpCode();
        }

        return new JSONResponse(
            [
                'status'  => 'error',
                'id'      => $id,
                'message' => $message
            ], $statusCode
        );
    }
}