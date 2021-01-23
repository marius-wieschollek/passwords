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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserMoveCommand
 *
 * @package OCA\Passwords\Command
 */
class UserMoveCommand extends AbstractInteractiveCommand {

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var IUserManager
     */
    protected IUserManager $userManager;

    /**
     * @var MoveUserDataHelper
     */
    protected MoveUserDataHelper $moveUserData;

    /**
     * @var DeleteUserDataHelper
     */
    protected DeleteUserDataHelper $deleteUserData;

    /**
     * UserMoveCommand constructor.
     *
     * @param IUserManager         $userManager
     * @param ConfigurationService $config
     * @param DeleteUserDataHelper $deleteUserData
     * @param MoveUserDataHelper   $moveUserData
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
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        parent::execute($input, $output);

        $users = $this->getUsers($input, $output);
        if($users === null) return 1;
        [$sourceUser, $targetUser] = $users;

        if(!$this->confirmMove($input, $output, $sourceUser->getDisplayName(), $targetUser->getDisplayName())) return 2;
        if(!$this->checkSourceHasData($output, $sourceUser)) return 3;
        if(!$this->checkTargetOverwrite($input, $output, $targetUser)) return 4;

        $output->write('Moving data ...');
        $this->moveUserData->moveUserData($sourceUser->getUID(), $targetUser->getUID());
        $output->write(' done');
        $output->writeln('');

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $sourceName
     * @param string          $targetName
     *
     * @return bool
     */
    protected function confirmMove(InputInterface $input, OutputInterface $output, string $sourceName, string $targetName): bool {
        return $this->requestConfirmation($input, $output, 'This command will move all data from "'.$sourceName.'" to "'.$targetName.'"');
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
        $userId = $targetUser->getUID();
        if(!$this->userHasData($userId)) return true;

        if(!$this->requestConfirmation($input, $output, 'The existing data of "'.$targetUser->getDisplayName().'" will be deleted permanently')) {
            return false;
        }

        $output->write('Deleting data ...');
        $this->deleteUserData->deleteUserData($userId);
        $output->write(' done');
        $output->writeln('');

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param IUser           $sourceUser
     *
     * @return bool
     * @throws Exception
     */
    protected function checkSourceHasData(OutputInterface $output, IUser $sourceUser): bool {
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