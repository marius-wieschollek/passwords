<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Admin;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Throwable;

/**
 * Class CacheController
 *
 * @package OCA\Passwords\Controller\Admin
 */
class CacheController extends ApiController {

    /**
     * @var FileCacheService
     */
    protected FileCacheService $fileCacheService;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var array
     */
    protected array $cacheLabels
        = [
            FileCacheService::DEFAULT_CACHE   => 'Default Cache',
            FileCacheService::AVATAR_CACHE    => 'Avatar Cache',
            FileCacheService::FAVICON_CACHE   => 'Favicon Cache',
            FileCacheService::PREVIEW_CACHE   => 'Preview Cache',
            FileCacheService::PASSWORDS_CACHE => 'Password Cache',
        ];

    /**
     * CacheController constructor.
     *
     * @param string               $appName
     * @param IRequest             $request
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $config
     */
    public function __construct(
        string $appName,
        IRequest $request,
        FileCacheService $fileCacheService,
        ConfigurationService $config
    ) {
        parent::__construct(
            $appName,
            $request,
            'GET, DELETE'
        );
        $this->fileCacheService = $fileCacheService;
        $this->config           = $config;
    }

    /**
     * @return JSONResponse
     */
    public function index(): JSONResponse {
        $info = $this->getCaches();

        return new JSONResponse($info);
    }

    /**
     * @param $id
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function show($id): JSONResponse {
        $info = $this->getCaches();

        if(isset($info[ $id ])) {
            return new JSONResponse($info[ $id ]);
        }

        throw new ApiException('Cache not found', Http::STATUS_NOT_FOUND);
    }

    /**
     * @param $id
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function destroy($id): JSONResponse {
        $info = $this->getCaches();

        if(!isset($info[ $id ])) {
            throw new ApiException('Cache not found', Http::STATUS_NOT_FOUND);
        } else if($info[ $id ]['clearable']) {
            $this->fileCacheService->clearCache($id);
        } else {
            throw new ApiException('Cache not clearable', Http::STATUS_UNAUTHORIZED);
        }

        return $this->show($id);
    }

    /**
     * @return array
     */
    protected function getCaches(): array {
        $caches = $this->fileCacheService->listCaches();
        $info   = [];

        foreach ($caches as $cache) {
            try {
                $info[ $cache ]              = $this->fileCacheService->getCacheInfo($cache);
                $info[ $cache ]['name']      = $cache;
                $info[ $cache ]['clearable'] = true;

                if(isset($this->cacheLabels[ $cache ])) {
                    $info[ $cache ]['label'] = $this->cacheLabels[ $cache ];
                } else {
                    $info[ $cache ]['label'] = '';
                }
            } catch (Throwable $e) {
            }
        }

        return $info;
    }
}