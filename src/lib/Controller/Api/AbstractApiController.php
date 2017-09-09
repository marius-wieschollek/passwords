<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 29.08.17
 * Time: 22:12
 */

namespace OCA\Passwords\Controller\Api;

use OCP\AppFramework\ApiController;
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
    protected function createResponse($response, int $statusCode = 200): JSONResponse {
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
        return new JSONResponse(
            [
                'status'  => 'error',
                // @TODO It's never a good idea to pass all error messages to the frontend
                'message' => $e->getMessage()
            ], 400
        );
    }
}