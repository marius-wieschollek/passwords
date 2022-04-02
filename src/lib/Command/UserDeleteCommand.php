<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use Exception;
use OCP\IUserManager;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\BackgroundJobService;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use OCA\Passwords\Helper\User\DeleteUserDataHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserDeleteCommand
 *
 * @package OCA\Passwords\Command
 */
class UserDeleteCommand extends AbstractInteractiveCommand {

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var IUserManager
     */
    protected IUserManager $userManager;

    /**
     * @var DeleteUserDataHelper
     */
    protected DeleteUserDataHelper $deleteUserData;

    /**
     * @var BackgroundJobService
     */
    protected BackgroundJobService $backgroundJobs;

    /**
     * UserDeleteCommand constructor.
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
             ->setDescription('Deletes the passwords app data of a user')
             ->addArgument('user', InputArgument::REQUIRED, 'The id of the user');
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
        $userId = $this->getUserId($input, $output);
        if($this->confirmDelete($input, $output, $userId)) {
            $output->write('Deleting passwords app data ...');
            $this->deleteUserData->deleteUserData($userId);
            $this->backgroundJobs->removeDeleteUserJob($userId);
            $output->write(' done');
            $output->writeln('');

            return 0;
        }

        return 1;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $userId
     *
     * @return bool
     */
    protected function confirmDelete(InputInterface $input, OutputInterface $output, string $userId): bool {
        $user     = $this->userManager->get($userId);
        $userName = $user === null ? $userId:$user->getDisplayName();

        return $this->requestConfirmation($input, $output, 'This command will delete all data for the passwords app from "'.$userName.'"');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return string
     */
    protected function getUserId(InputInterface $input, OutputInterface $output): string {
        $userId = $input->getArgument('user');
        $user = $this->userManager->get($userId);

        if($user === null) {
            $output->write(
                [
                    'Found no user for id "'.$userId.'" in database, will continue with provided user id.',
                    'If you are sure that the user was not deleted and should exist, you might have the wrong id.',
                    ''
                ],
                true
            );
        } else if($user->getUID() !== $userId) {
            $output->writeln('❗❗❗ Found user "'.$user->getDisplayName().'" for user id "'.$userId.'" but it has the id "'.$user->getUID().'" instead ❗❗❗');
            if(!$input->isInteractive()) {
                $output->writeln(['ignoring', '']);
                return $userId;
            }

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question('Type "change" to change the user id to "'.$user->getUID().'": ');
            $response = $helper->ask($input, $output, $question);

            if($response !== 'change') {
                $output->writeln(['Changed user id to '.$user->getUID(), '']);

                return $user->getUID();
            }

            $output->writeln(['Continuing with '.$userId, '']);
        }

        return $userId;
    }
}