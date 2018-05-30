<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCP\AppFramework\Http\JSONResponse;

class AuthorisationApiController extends AbstractApiController {

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     */
    public function info(): JSONResponse {
        return new JSONResponse(['none', 'password', 'totp-v1']);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     */
    public function login(): JSONResponse {
        $parameters = $this->getParameterArray();

        return new JSONResponse(['success' => false]);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function logout() {


    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function update() {


    }
}