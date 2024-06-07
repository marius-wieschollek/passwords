<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use Exception;
use OCA\Passwords\Db\Challenge;
use OCA\Passwords\Events\Challenge\AfterChallengeActivatedEvent;
use OCA\Passwords\Events\Challenge\AfterChallengeRevertedEvent;
use OCA\Passwords\Events\Challenge\BeforeChallengeActivatedEvent;
use OCA\Passwords\Events\Challenge\BeforeChallengeRevertedEvent;
use OCA\Passwords\Events\Challenge\ChallengeActivatedEvent;
use OCA\Passwords\Events\Challenge\ChallengeRevertedEvent;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\Challenge\ChallengeV1Helper;
use OCA\Passwords\Services\Object\ChallengeService;
use OCP\AppFramework\Http;
use OCP\EventDispatcher\IEventDispatcher;
use stdClass;

/**
 * Class UserChallengeService
 *
 * @package OCA\Passwords\Services
 */
class UserChallengeService {

    const USER_CHALLENGE_ID = 'user/challenge/id';

    /**
     * @var LoggingService
     */
    protected LoggingService $logger;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var SessionService
     */
    protected SessionService $sessionService;

    /**
     * @var ChallengeService
     */
    protected ChallengeService $challengeService;

    /**
     * @var ChallengeV1Helper
     */
    protected ChallengeV1Helper $challengeHelper;

    /**
     * @var IEventDispatcher
     */
    protected IEventDispatcher $eventDispatcher;

    /**
     * UserChallengeHelper constructor.
     *
     * @param LoggingService       $logger
     * @param ConfigurationService $config
     * @param SessionService       $sessionService
     * @param IEventDispatcher     $eventDispatcher
     * @param ChallengeService     $challengeService
     * @param ChallengeV1Helper    $challengeHelper
     */
    public function __construct(
        LoggingService $logger,
        ConfigurationService $config,
        SessionService $sessionService,
        IEventDispatcher $eventDispatcher,
        ChallengeService $challengeService,
        ChallengeV1Helper $challengeHelper
    ) {
        $this->config           = $config;
        $this->logger           = $logger;
        $this->sessionService   = $sessionService;
        $this->challengeService = $challengeService;
        $this->challengeHelper  = $challengeHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return bool
     */
    public function hasChallenge(): bool {
        try {
            return $this->config->hasUserValue(self::USER_CHALLENGE_ID);
        } catch(Exception $e) {
            $this->logger->logException($e);

            return false;
        }
    }

    /**
     * @return stdClass
     * @throws ApiException
     */
    public function getChallengeData(): stdClass {
        if($this->hasChallenge()) {
            $challenge        = $this->getDefaultChallenge();
            $clientData       = json_decode($challenge->getClientData());
            $clientData->type = $challenge->getType();

            return $clientData;
        }

        throw new ApiException('Not found', Http::STATUS_NOT_FOUND);
    }

    /**
     * @param string $secret
     *
     * @return bool
     * @throws ApiException
     */
    public function validateChallenge(string $secret): bool {
        try {
            $challenge = $this->getDefaultChallenge();
        } catch(Exception $e) {
            $this->logger->logException($e);

            return false;
        }

        $key = $this->challengeHelper->solveChallenge($challenge, $secret);
        $this->sessionService->set(SessionService::VALUE_USER_SECRET, $key);

        return true;
    }

    /**
     * @param array  $clientData
     * @param string $secret
     *
     * @return bool
     * @throws ApiException
     */
    public function setChallenge(array $clientData, string $secret): bool {
        $backup = $this->backupChallenge();
        try {
            $this->eventDispatcher->dispatchTyped(new BeforeChallengeActivatedEvent($clientData, $secret));

            /** @var $challenge Challenge */
            [$key, $challenge] = $this->challengeHelper->createChallenge($secret, $clientData);
            $this->challengeService->save($challenge);
            $this->sessionService->set(SessionService::VALUE_USER_SECRET, $key);
            $this->config->setUserValue(self::USER_CHALLENGE_ID, $challenge->getUuid());

            $this->eventDispatcher->dispatchTyped(new ChallengeActivatedEvent($challenge, $key));
            $this->eventDispatcher->dispatchTyped(new AfterChallengeActivatedEvent($challenge, $key));
        } catch(Exception $e) {
            $this->logger->logException($e);

            $this->revertChallenge($backup);

            return false;
        }

        try {
            if(isset($backup['challenge'])) $this->challengeService->delete($backup['challenge']);
        } catch(Exception $e) {
            $this->logger->logException($e);
        }

        return true;
    }

    /**
     * @return Challenge
     * @throws ApiException
     */
    protected function getDefaultChallenge(): Challenge {
        try {
            $id = $this->config->getUserValue(self::USER_CHALLENGE_ID);

            return $this->challengeService->findByUuid($id, true);
        } catch(Exception $e) {
            $this->logger->logException($e);

            throw new ApiException('Loading challenge failed', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
        }
    }

    /**
     * @return array
     * @throws ApiException
     */
    protected function backupChallenge(): array {
        $backup = [];
        if($this->hasChallenge()) {
            try {
                $backup = [
                    'challenge' => $this->getDefaultChallenge(),
                    'secret'    => $this->sessionService->get(SessionService::VALUE_USER_SECRET)
                ];
            } catch(Exception $e) {
                $this->logger->logException($e);

                throw new ApiException('Challenge update failed', Http::STATUS_INTERNAL_SERVER_ERROR, $e);
            }
        }

        return $backup;
    }

    /**
     * @param array $backup
     */
    protected function revertChallenge(array $backup): void {
        try {
            $this->eventDispatcher->dispatchTyped(new BeforeChallengeRevertedEvent($backup));
            if(isset($backup['challenge'])) {
                $this->config->setUserValue(self::USER_CHALLENGE_ID, $backup['challenge']->getUuid());
                $this->sessionService->set(SessionService::VALUE_USER_SECRET, $backup['secret']);
            } else {
                $this->config->deleteUserValue(self::USER_CHALLENGE_ID);
                $this->sessionService->unset(SessionService::VALUE_USER_SECRET);
            }
            $this->eventDispatcher->dispatchTyped(new ChallengeRevertedEvent($backup));
            $this->eventDispatcher->dispatchTyped(new AfterChallengeRevertedEvent($backup));
        } catch(Exception $e) {
            $this->logger->logException($e);
        }
    }
}