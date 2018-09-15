<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\AbstractObjectHelper;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class AbstractApiController
 *
 * @package OCA\Passwords\Controller
 */
abstract class AbstractApiController extends ApiController {

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated'];

    /**
     * AbstractApiController constructor.
     *
     * @param IRequest $request
     */
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
     * @param array $criteria
     *
     * @return array
     * @throws ApiException
     */
    protected function processSearchCriteria($criteria = []): array {
        $filters = [];
        foreach($criteria as $key => $value) {
            if(!in_array($key, $this->allowedFilterFields)) {
                throw new ApiException('Illegal field in search criteria: '.addslashes($key), 400);
            }

            if($value === 'true') {
                $value = true;
            } else if($value === 'false') {
                $value = false;
            } else if(is_array($value) && !in_array($value[0], AbstractObjectHelper::$filterOperators)) {
                throw new ApiException('Illegal operator in search criteria: '.addslashes($value[0]), 400);
            }

            $filters[ $key ] = $value;
        }

        return $filters;
    }

    /**
     * @return array
     */
    protected function getParameterArray(): array {
        $params = $this->request->getParams();
        unset($params['_route']);

        return $params;
    }
}