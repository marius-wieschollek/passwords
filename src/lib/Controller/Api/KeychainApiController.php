<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Controller\Api;

use Exception;
use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\Object\KeychainService;
use OCA\Passwords\Services\UserChallengeService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class KeychainApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class KeychainApiController extends AbstractApiController {

    /**
     * @var KeychainService
     */
    protected KeychainService $keychainService;

    /**
     * @var UserChallengeService
     */
    protected UserChallengeService $challengeService;

    /**
     * KeychainApiController constructor.
     *
     * @param IRequest             $request
     * @param KeychainService      $keychainService
     * @param UserChallengeService $challengeService
     */
    public function __construct(IRequest $request, KeychainService $keychainService, UserChallengeService $challengeService) {
        parent::__construct($request);
        $this->keychainService  = $keychainService;
        $this->challengeService = $challengeService;
    }

    /**
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function list(): JSONResponse {
        try {
            $results = $this->keychainService->getClientKeychainArray();
        } catch(Exception $e) {
            throw new ApiException('Reading user keychain failed', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
        }

        return $this->createJsonResponse($results);
    }

    /**
     * @param string $id
     * @param string $data
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function update(string $id, string $data): JSONResponse {
        if(!$this->challengeService->hasChallenge()) {
            throw new ApiException('Encryption not enabled');
        }

        try {
            $keychain = $this->keychainService->findByType($id, true);

            if($keychain->getScope() !== $keychain::SCOPE_CLIENT) {
                throw new ApiException('Keychain not found', Http::STATUS_NOT_FOUND);
            }

            $keychain->setData($data);
        } catch(DoesNotExistException $e) {
            $keychain = $this->keychainService->create($id, $data, Keychain::SCOPE_CLIENT);
        }

        $this->keychainService->save($keychain);

        return $this->createJsonResponse(['id' => $keychain->getType()]);
    }
}