<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Admin;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\AppSettingsService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class SettingsController
 *
 * @package OCA\Passwords\Controller\Admin
 */
class SettingsController extends ApiController {

    /**
     * @var AppSettingsService
     */
    protected AppSettingsService $settingsService;

    /**
     * SettingsController constructor.
     *
     * @param string             $appName
     * @param IRequest           $request
     * @param AppSettingsService $settingsService
     */
    public function __construct(
        string $appName,
        IRequest $request,
        AppSettingsService $settingsService
    ) {
        parent::__construct(
            $appName,
            $request,
            'PUT, GET, DELETE'
        );
        $this->settingsService = $settingsService;
    }

    /**
     * @return JSONResponse
     */
    public function index(): JSONResponse {
        $data = $this->settingsService->list();

        return new JSONResponse($data);
    }

    /**
     * @param $id
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function show($id): JSONResponse {
        $data = $this->settingsService->get($id);

        return new JSONResponse($data);
    }

    /**
     * @param $id
     * @param $value
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function update($id, $value): JSONResponse {
        if(!empty($value) || ($value === "0" && in_array($id, ['survey.server', 'entity.purge.timeout', 'settings.password.hash', 'backup.update.autorestore', 'encryption.ssev3.enabled']))) {
            $data = $this->settingsService->set($id, $value);

            return new JSONResponse($data);
        }

        return $this->destroy($id);
    }

    /**
     * @param $id
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function destroy($id): JSONResponse {
        $data = $this->settingsService->reset($id);

        return new JSONResponse($data);
    }
}