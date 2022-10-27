<?php
/*
 * @copyright 2022 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Controller\Actions;

use Exception;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\PasswordService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class RecoverHiddenController extends Controller {

    protected PasswordRevisionService $passwordRevisionService;
    protected FolderRevisionService   $folderRevisionService;
    protected TagRevisionService      $tagRevisionService;

    /**
     * @param                         $appName
     * @param IRequest                $request
     * @param PasswordRevisionService $passwordRevisionService
     * @param FolderRevisionService   $folderRevisionService
     * @param TagRevisionService      $tagRevisionService
     */
    public function __construct(
        $appName,
        IRequest $request,
        PasswordRevisionService $passwordRevisionService,
        FolderRevisionService $folderRevisionService,
        TagRevisionService $tagRevisionService,
    ) {
        parent::__construct($appName, $request);
        $this->passwordRevisionService = $passwordRevisionService;
        $this->folderRevisionService   = $folderRevisionService;
        $this->tagRevisionService      = $tagRevisionService;
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param bool $folders
     * @param bool $tags
     * @param bool $passwords
     * @param bool $passwordsInvisibleInFolder
     *
     * @return JSONResponse
     */
    public function execute(bool $folders, bool $tags, bool $passwords, bool $passwordsInvisibleInFolder): JSONResponse {

        $recoveredFolders   = $folders ? $this->recoverFolders():0;
        $recoveredTags      = $tags ? $this->recoverTags():0;
        $recoveredPasswords = $passwords || $passwordsInvisibleInFolder ? $this->recoverPasswords($passwords || $folders):0;

        return new JSONResponse(['success' => true, 'folders' => $recoveredFolders, 'tags' => $recoveredTags, 'passwords' => $recoveredPasswords], Http::STATUS_OK);
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function recoverFolders(): int {
        $folderRevisions = $this->folderRevisionService->findAllHidden();

        $recoveredFolders     = 0;
        $currentRevisionCache = [];
        foreach($folderRevisions as $folderRevision) {
            $modelUuid = $folderRevision->getModel();
            if(!isset($currentRevisionCache[ $modelUuid ])) {
                $currentRevisionCache[ $modelUuid ] = $this->folderRevisionService->findCurrentRevisionByModel($modelUuid)->getId();
            }

            if($currentRevisionCache[ $modelUuid ] === $folderRevision->getId()) {
                $folderRevision->setHidden(false);
                $this->folderRevisionService->save($folderRevision);
                $recoveredFolders++;
            }
        }

        return $recoveredFolders;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function recoverTags(): int {
        $tagRevisions = $this->tagRevisionService->findAllHidden();

        $recoveredTags        = 0;
        $currentRevisionCache = [];
        foreach($tagRevisions as $tagRevision) {
            $modelUuid = $tagRevision->getModel();
            if(!isset($currentRevisionCache[ $modelUuid ])) {
                $currentRevisionCache[ $modelUuid ] = $this->tagRevisionService->findCurrentRevisionByModel($modelUuid)->getId();
            }

            if($currentRevisionCache[ $modelUuid ] === $tagRevision->getId()) {
                $tagRevision->setHidden(false);
                $this->tagRevisionService->save($tagRevision);
                $recoveredTags++;
            }
        }

        return $recoveredTags;
    }

    /**
     * @param $allPasswords
     *
     * @return int
     * @throws Exception
     */
    protected function recoverPasswords($allPasswords): int {
        $passwordRevisions = $this->passwordRevisionService->findAllHidden();

        $recoveredPasswords   = 0;
        $currentRevisionCache = [];
        foreach($passwordRevisions as $passwordRevision) {
            $modelUuid = $passwordRevision->getModel();
            if(!isset($currentRevisionCache[ $modelUuid ])) {
                $currentRevisionCache[ $modelUuid ] = $this->passwordRevisionService->findCurrentRevisionByModel($modelUuid)->getId();
            }

            if($currentRevisionCache[ $modelUuid ] === $passwordRevision->getId()) {
                if(
                    !$allPasswords &&
                    $this->folderRevisionService->findCurrentRevisionByModel($passwordRevision->getModel())->isHidden()
                ) {
                    continue;
                }

                $passwordRevision->setHidden(false);
                $this->passwordRevisionService->save($passwordRevision);
                $recoveredPasswords++;
            }
        }

        return $recoveredPasswords;
    }
}