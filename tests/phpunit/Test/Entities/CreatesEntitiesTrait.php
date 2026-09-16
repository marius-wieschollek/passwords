<?php

namespace OCA\Test\Passwords\Entities;

use OCA\Passwords\Db\EntityInterface;
use OCA\Passwords\Db\Folder;
use OCA\Passwords\Db\FolderRevision;
use OCA\Passwords\Db\Password;
use OCA\Passwords\Db\PasswordRevision;
use OCA\Passwords\Db\Tag;
use OCA\Passwords\Db\TagRevision;

trait CreatesEntitiesTrait {
    public function createPasswordModel(array $properties = []): Password {
        return $this->createEntity(Password::class, $properties);
    }

    public function createPasswordModelMock(array $properties = []): Password {
        return $this->createEntityMock(Password::class, $properties);
    }

    public function createPasswordRevision(array $properties = []): PasswordRevision {
        return $this->createRevisionEntity(PasswordRevision::class, $properties);
    }

    public function createPasswordRevisionMock(array $properties = []): PasswordRevision {
        return $this->createRevisionEntityMock(PasswordRevision::class, $properties);
    }

    public function createFolderModel(array $properties = []): Folder {
        return $this->createEntity(Folder::class, $properties);
    }

    public function createFolderModelMock(array $properties = []): Folder {
        return $this->createEntityMock(Folder::class, $properties);
    }

    public function createFolderRevision(array $properties = []): FolderRevision {
        return $this->createRevisionEntity(FolderRevision::class, $properties);
    }

    public function createFolderRevisionMock(array $properties = []): FolderRevision {
        return $this->createRevisionEntityMock(FolderRevision::class, $properties);
    }

    public function createTagModel(array $properties = []): Tag {
        return $this->createEntity(Tag::class, $properties);
    }

    public function createTagModelMock(array $properties = []): Tag {
        return $this->createEntityMock(Tag::class, $properties);
    }

    public function createTagRevision(array $properties = []): TagRevision {
        return $this->createRevisionEntity(TagRevision::class, $properties);
    }

    public function createTagRevisionMock(array $properties = []): TagRevision {
        return $this->createRevisionEntityMock(TagRevision::class, $properties);
    }

    protected function createRevisionEntity($class, array $properties = []): EntityInterface {
        $tagProperties = array_merge(
            ['hidden' => false],
            $properties
        );
        return $this->createEntity($class, $tagProperties);
    }

    protected function createRevisionEntityMock($class, array $properties = []): EntityInterface {
        $tagProperties = array_merge(['hidden' => false], $properties);

        return $this->createEntityMock($class, $tagProperties);
    }


    protected function createEntity($class, array $properties = []): EntityInterface {
        return $class::fromParams($properties);
    }

    protected function createEntityMock($class, array $properties = []): EntityInterface {
        $tagProperties = array_merge(['hidden' => false], $properties);
        $calls = [];

        $entity = $this->createMock($class);
        $entity->method('__call')->willReturnCallback(
            function (string $method, array $args) use (&$tagProperties, &$calls) {
                if($method === 'getUnitCalls') {
                    return $calls;
                }

                $calls[] = $method;
                $field = lcfirst(substr($method, 3));
                if (str_starts_with($method, 'set')) {
                    $tagProperties[$field] = $args[0];
                    return null;
                }

                if (isset($tagProperties[$field]) && is_array($tagProperties[$field]) && is_array($tagProperties[$field]['onConsecutive'])) {
                    return empty($tagProperties[$field]['onConsecutive']) ? null : array_shift(
                        $tagProperties[$field]['onConsecutive']
                    );
                }


                return $tagProperties[$field] ?? null;
            }
        );

        return $entity;
    }
}