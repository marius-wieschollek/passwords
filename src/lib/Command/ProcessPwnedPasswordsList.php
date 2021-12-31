<?php
/*
 * @copyright 2021 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Command;

use Exception;
use OCA\Passwords\Exception\SecurityCheck\BreachedPasswordsZipAccessException;
use OCA\Passwords\Helper\SecurityCheck\AbstractSecurityCheckHelper;
use OCA\Passwords\Helper\SecurityCheck\BigLocalDbSecurityCheckHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCP\Files\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class ProcessPwnedPasswordsList extends Command {

    protected FileCacheService     $fileCacheService;
    protected ConfigurationService $config;

    public function __construct(FileCacheService $fileCacheService, ConfigurationService $configurationService) {
        $this->fileCacheService = $fileCacheService->getCacheService($fileCacheService::PASSWORDS_CACHE);
        $this->config           = $configurationService;
        parent::__construct();
    }

    /**
     *
     */
    protected function configure(): void {
        $this->setName('passwords:pwned-list:process')
             ->setDescription('Convert the haveibeenpwned passwords file for the passwords app')
             ->addArgument('file', InputArgument::REQUIRED, 'The path to the file')
             ->addOption('size', 's', InputOption::VALUE_REQUIRED, 'Amount of passwords to process in millions', '25')
             ->addOption('mode', 'm', InputOption::VALUE_OPTIONAL, 'Mode for packing the passwords (auto|json|gzip)', 'auto')
             ->addOption('import', 'i', InputOption::VALUE_NONE, 'Import the passwords onto the local system');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws NotFoundException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        ini_set('memory_limit', -1);
        [$file, $size, $mode, $import] = $this->getSettings($input, $output);

        $hashes = $this->readHashes($file, $size);
        $file   = $this->writeZipFile($size, $mode, $hashes, $import);

        if($import) {
            $output->writeln('Password database updated');
            $this->config->setAppValue(AbstractSecurityCheckHelper::CONFIG_DB_TYPE, BigLocalDbSecurityCheckHelper::PASSWORD_DB);
            $this->config->setAppValue(BigLocalDbSecurityCheckHelper::CONFIG_DB_VERSION, BigLocalDbSecurityCheckHelper::PASSWORD_VERSION);
        }
        $output->writeln("Created {$file}");

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return array
     * @throws NotFoundException
     * @throws Exception
     */
    protected function getSettings(InputInterface $input, OutputInterface $output): array {
        $file   = realpath($input->getArgument('file'));
        $size   = intval($input->getOption('size'));
        $mode   = $input->getOption('mode');
        $import = $input->getOption('import');

        if(!is_file($file) || !is_readable($file)) {
            throw new NotFoundException("File not found or not readable: {$file}");
        }
        if($size < 1) {
            throw new Exception("Invalid size specified: {$size}");
        }
        if(!in_array($mode, ['auto', 'json', 'gzip'])) {
            throw new Exception("Invalid mode specified: {$mode}");
        }
        if($mode === 'gzip' && !extension_loaded('zlib')) {
            throw new Exception("Mode gzip not supported. Install zlib extension.");
        }
        if($mode === 'auto') {
            $mode = extension_loaded('zlib') ? 'gzip':'json';
        }

        $output->writeln('Processing'.($import ? ' and importing':'')." {$size} million entries from {$file} in {$mode} mode.");

        return [$file, $size, $mode, $import];
    }

    /**
     * @param mixed $file
     * @param mixed $size
     *
     * @return array
     * @throws Exception
     */
    protected function readHashes(mixed $file, mixed $size): array {
        $handle = fopen($file, 'r');
        if(!$handle) {
            throw new Exception("Could not open file: {$file}");
        }

        $order  = null;
        $hashes = [];
        for($i = 0; $i < $size * 1000000; $i++) {
            $line = fgets($handle);
            if(!$line) {
                throw new Exception("File contains invalid lines");
            }

            [$sha1, $count] = explode(':', $line);
            if(!$count || intval($count) > $order && $order !== null) {
                throw new Exception('File appears to be unordered. Please provide hashes ordered by count.');
            }
            $order = intval($count);

            if(strlen($sha1) !== 40) {
                throw new Exception('Invalid SHA-1 hash encountered.');
            }

            $sha1  = strtolower($sha1);
            $start = substr($sha1, 0, AbstractSecurityCheckHelper::HASH_FILE_KEY_LENGTH);
            if(!isset($hashes[ $start ])) $hashes[ $start ] = [];
            $hashes[ $start ][] = $sha1;
        }

        return $hashes;
    }

    /**
     * @param mixed $size
     * @param mixed $mode
     * @param array $hashes
     * @param mixed $import
     *
     * @return string
     */
    protected function writeZipFile(int $size, string $mode, array $hashes, bool $import): string {
        $fileName = "{$size}-million-{$mode}.zip";
        if(is_file($fileName)) {
            unlink($fileName);
        }

        $zip    = new ZipArchive();
        $result = $zip->open($fileName, ZipArchive::CREATE);
        if($result !== true) {
            throw new BreachedPasswordsZipAccessException($result);
        }

        if($import) {
            $this->fileCacheService->clearCache();
        }

        foreach($hashes as $key => $values) {
            $file = $mode ? "{$key}.json.gz":"{$key}.json";
            $data = json_encode(array_unique($values));

            if($mode === 'gzip') $data = gzcompress($data);
            $zip->addFromString($file, $data);

            if($import) {
                $this->fileCacheService->putFile($file, $data);
            }
        }
        $zip->close();

        return $fileName;
    }
}