<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Command;

use OCA\Passwords\Exception\Command\NonInteractiveShellException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class AbstractInteractiveCommand
 *
 * @package OCA\Passwords\Command
 */
abstract class AbstractInteractiveCommand extends Command {

    /**
     * AbstractInteractiveCommand constructor.
     *
     * @param string|null $name
     */
    public function __construct(string $name = null) {
        parent::__construct($name);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws NonInteractiveShellException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        if(!$input->isInteractive() && !$input->getOption('no-interaction')) {
            throw new NonInteractiveShellException();
        } else if(!$input->isInteractive()) {
            $output->writeln('"--no-interaction" is set, will assume yes for all questions.');
            $output->writeln('');
        }

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $description
     *
     * @return bool
     */
    protected function requestConfirmation(InputInterface $input, OutputInterface $output, string $description): bool {
        $output->writeln("❗❗❗ {$description} ❗❗❗");
        if(!$input->isInteractive()) {
            $output->writeln('');
            return true;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question('Type "yes" to confirm this: ');
        $yes      = $helper->ask($input, $output, $question);
        $output->writeln('');

        if($yes !== 'yes') {
            $output->writeln('aborting');

            return false;
        }

        return true;
    }
}