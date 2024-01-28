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

namespace OCA\Passwords\Controller\Actions;

use Exception;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\PasswordRevisionService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
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
     * @param bool $folders
     * @param bool $tags
     * @param bool $passwords
     * @param bool $passwordsInvisibleInFolder
     * @param bool $invisibleInTrash
     *
     * @return JSONResponse
     * @throws Exception
     */
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function execute(bool $folders, bool $tags, bool $passwords, bool $passwordsInvisibleInFolder, bool $invisibleInTrash): JSONResponse {

        $recoveredFolders   = $folders || $invisibleInTrash ? $this->recoverFolders($folders):0;
        $recoveredTags      = $tags || $invisibleInTrash ? $this->recoverTags($tags):0;
        $recoveredPasswords = $passwords || $passwordsInvisibleInFolder || $invisibleInTrash ? $this->recoverPasswords($passwords || $folders, $invisibleInTrash):0;

        return new JSONResponse(['success' => true, 'folders' => $recoveredFolders, 'tags' => $recoveredTags, 'passwords' => $recoveredPasswords], Http::STATUS_OK);
    }

    /**
     * @param bool $recoverAll
     *
     * @return int
     * @throws Exception
     */
    protected function recoverFolders(bool $recoverAll): int {
        $folderRevisions = $this->folderRevisionService->findAllHidden();

        $recoveredFolders     = 0;
        $currentRevisionCache = [];
        foreach($folderRevisions as $folderRevision) {
            $modelUuid = $folderRevision->getModel();
            if(!isset($currentRevisionCache[ $modelUuid ])) {
                $currentRevisionCache[ $modelUuid ] = $this->folderRevisionService->findCurrentRevisionByModel($modelUuid)->getId();
            }

            if($currentRevisionCache[ $modelUuid ] === $folderRevision->getId()) {
                if(!$recoverAll && !$folderRevision->getTrashed()) {
                    continue;
                }

                $folderRevision->setHidden(false);
                $this->folderRevisionService->save($folderRevision);
                $recoveredFolders++;
            }
        }

        return $recoveredFolders;
    }

    /**
     * @param bool $recoverAll
     *
     * @return int
     * @throws Exception
     */
    protected function recoverTags(bool $recoverAll): int {
        $tagRevisions = $this->tagRevisionService->findAllHidden();

        $recoveredTags        = 0;
        $currentRevisionCache = [];
        foreach($tagRevisions as $tagRevision) {
            $modelUuid = $tagRevision->getModel();
            if(!isset($currentRevisionCache[ $modelUuid ])) {
                $currentRevisionCache[ $modelUuid ] = $this->tagRevisionService->findCurrentRevisionByModel($modelUuid)->getId();
            }

            if($currentRevisionCache[ $modelUuid ] === $tagRevision->getId()) {
                if(!$recoverAll && !$tagRevision->getTrashed()) {
                    continue;
                }

                $tagRevision->setHidden(false);
                $this->tagRevisionService->save($tagRevision);
                $recoveredTags++;
            }
        }

        return $recoveredTags;
    }

    /**
     * @param bool $recoverAll
     * @param bool $inTrash
     *
     * @return int
     * @throws Exception
     */
    protected function recoverPasswords(bool $recoverAll, bool $inTrash): int {
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
                    !$recoverAll &&
                    (!$inTrash || !$passwordRevision->getTrashed()) &&
                    $this->folderRevisionService->findCurrentRevisionByModel($passwordRevision->getFolder())->isHidden()
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