<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use Exception;
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use OCA\Passwords\Helper\User\MoveUserDataHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class TransferOwnershipCommand
 *
 * @package OCA\Passwords\Command
 */
class UserMoveCommand extends Command {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var IUserManager
     */
    protected $userManager;

    /**
     * @var MoveUserDataHelper
     */
    protected $moveUserData;

    /**
     * @var DeleteUserDataHelper
     */
    protected $deleteUserData;

    /**
     * TransferOwnershipCommand constructor.
     *
     * @param IUserManager         $userManager
     * @param ConfigurationService $config
     * @param DeleteUserDataHelper $deleteUserData
     * @param string|null          $name
     */
    public function __construct(IUserManager $userManager, ConfigurationService $config, DeleteUserDataHelper $deleteUserData, MoveUserDataHelper $moveUserData, string $name = null) {
        parent::__construct($name);
        $this->userManager    = $userManager;
        $this->config         = $config;
        $this->deleteUserData = $deleteUserData;
        $this->moveUserData   = $moveUserData;
    }

    /**
     *
     */
    protected function configure() {
        $this->setName('passwords:user:move')
             ->setDescription('Moves all data from one user account to another')
             ->addArgument('source_user', InputArgument::REQUIRED, 'The id of the user to move from')
             ->addArgument('target_user', InputArgument::REQUIRED, 'The id of the user to move to');
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
        $users = $this->getUsers($input, $output);
        if($users === null) return 1;
        [$sourceUser, $targetUser] = $users;

        if(!$this->confirmMove($input, $output, $sourceUser->getDisplayName(), $targetUser->getDisplayName())) return 2;
        if(!$this->checkSourceHasData($input, $output, $sourceUser)) return 3;
        if(!$this->checkTargetOverwrite($input, $output, $targetUser)) return 4;

        $output->write('Moving data ...');
        $this->moveUserData->moveUserData($sourceUser->getUID(), $targetUser->getUID());
        $output->write(' done');
        $output->writeln('');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function confirmMove(InputInterface $input, OutputInterface $output, string $sourceName, string $targetName): bool {
        if($input->getOption('no-interaction')) return true;

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new Question('Type "yes" to confirm that you want to move all data from "'.$sourceName.'" to "'.$targetName.'": ');
        $yes      = $helper->ask($input, $output, $question);

        if($yes !== 'yes') {
            $output->writeln('aborting');

            return false;
        }

        return true;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return IUser[]|null
     */
    protected function getUsers(InputInterface $input, OutputInterface $output): ?array {
        $sourceUserId = $input->getArgument('source_user');
        $sourceUser   = $this->userManager->get($sourceUserId);
        if($sourceUser === null) {
            $output->writeln('The source user does not exist');

            return null;
        }

        $targetUserId = $input->getArgument('target_user');
        $targetUser   = $this->userManager->get($targetUserId);
        if($targetUser === null) {
            $output->writeln('The source user does not exist');

            return null;
        }

        if($targetUser === $sourceUser) {
            $output->writeln('The source user and the target user are the same');

            return null;
        }

        return [$sourceUser, $targetUser];
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param IUser           $targetUser
     *
     * @return bool
     * @throws Exception
     */
    protected function checkTargetOverwrite(InputInterface $input, OutputInterface $output, IUser $targetUser): bool {
        if($input->getOption('no-interaction')) return true;

        $userId = $targetUser->getUID();
        if(!$this->userHasData($userId)) return true;

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $output->writeln('The user "'.$targetUser->getDisplayName().'" already has data.');
        $question = new Question('Type "yes" to confirm that you want to overwrite the existing data of "'.$targetUser->getDisplayName().'": ');
        $yes      = $helper->ask($input, $output, $question);

        if($yes !== 'yes') {
            $output->writeln('aborting');

            return false;
        }

        $output->write('Deleting data ...');
        $this->deleteUserData->deleteUserData($userId);
        $output->write(' done');
        $output->writeln('');

        return true;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param IUser           $sourceUser
     *
     * @return bool
     * @throws Exception
     */
    protected function checkSourceHasData(InputInterface $input, OutputInterface $output, IUser $sourceUser): bool {
        $userId = $sourceUser->getUID();
        if($this->userHasData($userId)) return true;

        $output->writeln('The user "'.$sourceUser->getDisplayName().'" has no data.');
        $output->writeln('aborting');

        return false;
    }

    /**
     * @param string $userId
     *
     * @return bool
     * @throws Exception
     */
    protected function userHasData(string $userId): bool {
        return $this->config->hasUserValue('SSEv1UserKey', $userId) ||
               $this->config->hasUserValue('user/challenge/id', $userId);
    }
}