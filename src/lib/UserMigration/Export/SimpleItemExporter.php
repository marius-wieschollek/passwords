<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\UserMigration\Export;

use OCA\Passwords\Services\Object\ChallengeService;
use OCA\Passwords\Services\Object\KeychainService;
use OCA\Passwords\Services\Object\PasswordTagRelationService;

class SimpleItemExporter {

    public function __construct(protected ChallengeService $challengeService, protected KeychainService $keychainService, protected PasswordTagRelationService $passwordTagRelationService) {
    }

    public function exportData($userId) {
        $rawChallenges = $this->challengeService->findByUserId($userId, true);
        $challenges = [];
        foreach($rawChallenges as $challenge) {
            $challenges[] = $challenge->toArray();
        }

        $rawKeychains = $this->keychainService->findByUserId($userId, true);
        $keychains = [];
        foreach($rawKeychains as $keychain) {
            $keychains[] = $keychain->toArray();
        }

        $rawTagRelations = $this->passwordTagRelationService->findByUserId($userId);
        $tagRelations = [];
        foreach($rawTagRelations as $tagRelation) {
            $tagRelations[] = $tagRelation->toArray();
        }

        return [
            'challenges' => $challenges,
            'keychains' => $keychains,
            'tagRelations' => $tagRelations,
        ];
    }
}