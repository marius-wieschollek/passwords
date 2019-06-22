<?php

namespace OCA\Passwords\Helper\Challenge;

use OCA\Passwords\Db\Challenge;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\Object\ChallengeService;
use OCP\AppFramework\Http;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class ChallengeV1Helper {

    /**
     * @var ICrypto
     */
    protected $crypto;
    /**
     * @var ChallengeService
     */
    private $challengeService;
    /**
     * @var ISecureRandom
     */
    private $secureRandom;

    /**
     * ChallengeV1Helper constructor.
     *
     * @param ICrypto          $crypto
     * @param ChallengeService $challengeService
     * @param ISecureRandom    $secureRandom
     */
    public function __construct(ICrypto $crypto, ChallengeService $challengeService, ISecureRandom $secureRandom) {
        $this->crypto           = $crypto;
        $this->challengeService = $challengeService;
        $this->secureRandom     = $secureRandom;
    }

    /**
     * @param Challenge $challenge
     * @param string    $secret
     *
     * @return string
     * @throws ApiException
     */
    public function solveChallenge(Challenge $challenge, string $secret): string {
        if(strlen($secret) !== 64) {
            throw new ApiException('Secret length invalid', HTTP::STATUS_BAD_REQUEST);
        }

        $serverData   = json_decode($challenge->getServerData());
        $encryptedKey = $challenge->getSecret();

        try {
            return $this->crypto->decrypt($encryptedKey, $serverData->salt.$secret);
        } catch(\Exception $e) {
            throw new ApiException('Password invalid', HTTP::STATUS_UNAUTHORIZED);
        }
    }

    /**
     * @param string $secret
     * @param array  $salts
     *
     * @return array
     * @throws ApiException
     */
    public function createChallenge(string $secret, array $salts): array {
        if(strlen($secret) !== 64) {
            throw new ApiException('Secret length invalid', HTTP::STATUS_BAD_REQUEST);
        }

        if(strlen($salts[0]) < 512 || strlen($salts[1]) !== 128 || strlen($salts[2]) !== 32) {
            throw new ApiException('Salt length invalid', HTTP::STATUS_BAD_REQUEST);
        }

        $key  = $this->secureRandom->generate(512);
        $salt = $this->secureRandom->generate(512);

        $encryptedKey = $this->crypto->encrypt($key, $salt.$secret);
        $serverData   = json_encode(['salt' => $salt]);

        ksort($salts);
        $clientData = json_encode(['salts' => $salts]);

        $challenge = $this->challengeService->create(Challenge::TYPE_PWD_V1R1, $encryptedKey, $clientData, $serverData);

        return [$key, $challenge];
    }
}