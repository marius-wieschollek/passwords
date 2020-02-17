<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use Exception;
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Services\BackgroundJobService;
use OCA\Passwords\Services\ConfigurationService;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 *
 */
class UserDeleteCommand extends Command {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var DeleteUserDataHelper
     */
    protected $deleteUserData;

    /**
     * @var BackgroundJobService
     */
    protected $backgroundJobs;

    /**
     * TransferOwnershipCommand constructor.
     *
     * @param IUserManager         $userManager
     * @param ConfigurationService $config
     * @param DeleteUserDataHelper $deleteUserData
     * @param BackgroundJobService $backgroundJobs
     * @param string|null          $name
     */
    public function __construct(IUserManager $userManager, ConfigurationService $config, DeleteUserDataHelper $deleteUserData, BackgroundJobService $backgroundJobs, string $name = null) {
        parent::__construct($name);
        $this->userManager    = $userManager;
        $this->config         = $config;
        $this->deleteUserData = $deleteUserData;
        $this->backgroundJobs = $backgroundJobs;
    }

    /**
     *
     */
    protected function configure() {
        $this->setName('passwords:user:delete')
             ->setDescription('Deletes the data of a user')
             ->addArgument('user', InputArgument::REQUIRED, 'The id of the user');
        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $userId = $input->getArgument('user');
        if($this->confirmDelete($input, $output, $userId)) {
            $output->write('Deleting data ...');
            $this->deleteUserData->deleteUserData($userId);
            $this->backgroundJobs->removeDeleteUserJob($userId);
            $output->write(' done');
            $output->writeln('');
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function confirmDelete(InputInterface $input, OutputInterface $output, string $userId): bool {
        if($input->getOption('no-interaction')) return true;

        $user     = $this->userManager->get($userId);
        $userName = $user === null ? $userId:$user->getDisplayName();

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new Question('Type "yes" to confirm that you want to delete all data from "'.$userName.'": ');
        $yes      = $helper->ask($input, $output, $question);

        if($yes !== 'yes') {
            $output->writeln('aborting');

            return false;
        }

        return true;
    }

}