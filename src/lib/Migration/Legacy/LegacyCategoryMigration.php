<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 28.12.17
 * Time: 13:00
 */

namespace OCA\Passwords\Migration\Legacy;

use OCA\Passwords\Db\Legacy\LegacyCategory;
use OCA\Passwords\Db\Legacy\LegacyCategoryMapper;
use OCA\Passwords\Db\TagRevision;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\TagRevisionService;
use OCA\Passwords\Services\Object\TagService;
use OCP\Migration\IOutput;

/**
 * Class LegacyCategoryMigration
 *
 * @package OCA\Passwords\Migration
 */
class LegacyCategoryMigration {

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * @var LegacyCategoryMapper
     */
    protected $categoryMapper;

    /**
     * @var TagRevisionService
     */
    protected $tagRevisionService;

    /**
     * LegacyCategoryMigration constructor.
     *
     * @param TagService           $tagService
     * @param LegacyCategoryMapper $categoryMapper
     * @param TagRevisionService   $tagRevisionService
     */
    public function __construct(
        TagService $tagService,
        LegacyCategoryMapper $categoryMapper,
        TagRevisionService $tagRevisionService
    ) {
        $this->tagService         = $tagService;
        $this->categoryMapper     = $categoryMapper;
        $this->tagRevisionService = $tagRevisionService;
    }

    /**
     * @param IOutput $output
     *
     * @return TagRevision[]
     */
    public function migrateCategories(IOutput $output): array {
        $categories = $this->categoryMapper->findAll();
        $tags       = [];

        $count = count($categories);
        $output->info("Migrating categories (total: {$count})");
        $output->startProgress($count);
        foreach($categories as $category) {
            try {
                $tags[ $category->getId() ] = $this->migrateCategory($category);
            } catch(\Throwable $e) {
                $output->warning(
                    "Failed migrating category #{$category->getId()}: {$e->getMessage()} in {$e->getFile()} line ".$e->getLine()
                );
            }
            $output->advance(1);
        }
        $output->finishProgress();

        return $tags;
    }

    /**
     * @param LegacyCategory $category
     *
     * @return TagRevision
     * @throws \OCA\Passwords\Exception\ApiException
     * @throws \Exception
     */
    protected function migrateCategory(LegacyCategory $category): TagRevision {
        $tagModel    = $this->tagService->create();
        $tagRevision = $this->tagRevisionService->create(
            $tagModel->getUuid(),
            trim($category->getCategoryName()),
            '#'.trim($category->getCategoryColour()),
            EncryptionService::CSE_ENCRYPTION_NONE,
            time(),
            false,
            false,
            false
        );

        $tagRevision->setUserId($category->getUserId());
        $this->tagRevisionService->save($tagRevision);

        $tagModel->setUserId($category->getUserId());
        $tagModel->setRevision($tagRevision->getUuid());
        $this->tagService->save($tagModel);

        return $tagRevision;
    }
}