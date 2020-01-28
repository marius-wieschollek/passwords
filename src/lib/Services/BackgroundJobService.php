<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Cron\CheckNightlyUpdates;
use OCA\Passwords\Cron\ProcessDeletedUser;
use OCP\BackgroundJob\IJobList;

/**
 * Class BackgroundJobService
 *
 * @package OCA\Passwords\Services
 */
class BackgroundJobService {

    /**
     * @var IJobList
     */
    protected $jobList;

    /**
     * BackgroundJobService constructor.
     *
     * @param IJobList $jobList
     */
    public function __construct(IJobList $jobList) {
        $this->jobList = $jobList;
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    public function addDeleteUserJob(string $userId): bool {
        return $this->add(ProcessDeletedUser::class, $userId);
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    public function hasDeleteUserJob(string $userId): bool {
        return $this->has(ProcessDeletedUser::class, $userId);
    }

    /**
     * @param string $userId
     *
     * @return bool
     */
    public function removeDeleteUserJob(string $userId): bool {
        return $this->remove(ProcessDeletedUser::class, $userId);
    }

    /**
     * @return bool
     */
    public function addNightlyUpdates(): bool {
        return $this->add(CheckNightlyUpdates::class);
    }

    /**
     * @return bool
     */
    public function hasNightlyUpdates(): bool {
        return $this->has(CheckNightlyUpdates::class);
    }

    /**
     * @return bool
     */
    public function removeNightlyUpdates(): bool {
        return $this->remove(CheckNightlyUpdates::class);
    }

    /**
     * @param string $job
     * @param null   $argument
     *
     * @return bool
     */
    public function add(string $job, $argument = null): bool {
        if(!$this->jobList->has($job, $argument)) {
            $this->jobList->add($job, $argument);

            return true;
        }

        return false;
    }

    /**
     * @param string $job
     * @param null   $argument
     *
     * @return bool
     */
    public function has(string $job, $argument = null): bool {
        return $this->jobList->has($job, $argument);
    }

    /**
     * @param string $job
     * @param null   $argument
     *
     * @return bool
     */
    public function remove(string $job, $argument = null): bool {
        if($this->jobList->has($job, $argument)) {
            $this->jobList->remove($job, $argument);

            return true;
        }

        return false;
    }
}