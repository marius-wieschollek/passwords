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

namespace OCA\Passwords\Command;

use Exception;
use OCA\Passwords\Exception\SecurityCheck\BreachedPasswordsZipAccessException;
use OCA\Passwords\Provider\SecurityCheck\AbstractSecurityCheckProvider;
use OCA\Passwords\Provider\SecurityCheck\BigDbPlusHibpSecurityCheckProvider;
use OCA\Passwords\Provider\SecurityCheck\BigLocalDbSecurityCheckProvider;
use OCA\Passwords\Provider\SecurityCheck\SmallLocalDbSecurityCheckProvider;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\Files\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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
             ->setDescription('Convert the haveibeenpwned pwned passwords list for the passwords app')
             ->addArgument('file', InputArgument::OPTIONAL, 'The path to pwned passwords hash file', 'pwnedpasswords.txt')
             ->addOption('size', 's', InputOption::VALUE_REQUIRED, 'Amount of hashes to import in millions', '25')
             ->addOption('mode', 'm', InputOption::VALUE_REQUIRED, 'Mode for packing the hashes (file|json|gzip|import). Use import to import directly, otherwise a ZIP file will be created', 'import');
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
        [$file, $size, $mode] = $this->getSettings($input, $output);

        $hashes = $this->readHashes($file, $size, $output);
        if($mode === 'import') {
            $this->importHashes($hashes, $output);
        } else {
            $this->writeZipFile($size, $mode, $hashes, $output);
        }

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
        $file = realpath($input->getArgument('file'));
        $size = intval($input->getOption('size'));
        $mode = $input->getOption('mode');

        if(!is_file($file) || !is_readable($file)) {
            throw new NotFoundException("File not found or not readable: {$file}");
        }
        if($size < 1) {
            throw new Exception("Invalid size specified: {$size}");
        }
        if(!in_array($mode, ['file', 'json', 'gzip', 'import'])) {
            throw new Exception("Invalid mode specified: {$mode}");
        }
        if($mode === 'gzip' && !extension_loaded('zlib')) {
            throw new Exception("Mode gzip not supported. Install zlib extension.");
        }
        if($mode === 'file') {
            $mode = extension_loaded('zlib') ? 'gzip':'json';
        }

        $output->writeln(($mode === 'import' ? 'Importing':'Processing')." {$size} million entries from {$file} in {$mode} mode.");

        return [$file, $size, $mode];
    }

    /**
     * @param mixed           $file
     * @param mixed           $size
     * @param OutputInterface $output
     *
     * @return array
     * @throws Exception
     */
    protected function readHashes(mixed $file, mixed $size, OutputInterface $output): array {
        $hashPrevalence = $this->getHashPrevalence($file, $output);

        [$minPrevalence, $minPrevalenceAmount] = $this->getMinimumPrevalence($hashPrevalence, $size);
        unset($hashPrevalence);

        return $this->getHashesByPrevalence($file, $minPrevalence, $minPrevalenceAmount, $size, $output);
    }

    /**
     * @param mixed           $size
     * @param mixed           $mode
     * @param array           $hashes
     * @param OutputInterface $output
     *
     * @return void
     * @throws BreachedPasswordsZipAccessException
     */
    protected function writeZipFile(int $size, string $mode, array $hashes, OutputInterface $output): void {
        $output->writeln("Creating export file…");
        $fileName = "{$size}m-v".BigLocalDbSecurityCheckProvider::PASSWORD_VERSION."-{$mode}.zip";
        if(is_file($fileName)) {
            unlink($fileName);
        }

        $zip    = new ZipArchive();
        $result = $zip->open($fileName, ZipArchive::CREATE);
        if($result !== true) {
            throw new BreachedPasswordsZipAccessException($result);
        }

        $gzip        = $mode === 'gzip';
        $fileExt     = $gzip ? 'json.gz':'json';
        $progressBar = new ProgressBar($output, count($hashes));
        $progressBar->start();

        foreach($hashes as $key => $values) {
            $file = "{$key}.{$fileExt}";
            $data = json_encode(array_unique($values));

            if($gzip) $data = gzcompress($data);
            $zip->addFromString($file, $data);
            $progressBar->advance();
        }
        $zip->close();
        $progressBar->finish();
        $output->writeln('');

        $output->writeln("Created {$fileName}");
    }

    /**
     * @param array           $hashes
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function importHashes(array $hashes, OutputInterface $output): void {
        $output->writeln("Importing hashes…");
        $this->fileCacheService->clearCache();

        $gzip    = extension_loaded('zlib');
        $fileExt = $gzip ? 'json.gz':'json';

        $progressBar = new ProgressBar($output, count($hashes));
        $progressBar->start();

        foreach($hashes as $key => $values) {
            $data = json_encode(array_unique($values));
            if($gzip) $data = gzcompress($data);
            $this->fileCacheService->putFile("{$key}.{$fileExt}", $data);
            $progressBar->advance();
        }
        $progressBar->finish();
        $output->writeln('');

        $installed = $this->config->getAppValue('service/security', HelperService::SECURITY_HIBP);
        if($installed === HelperService::SECURITY_BIGDB_HIBP) {
            $this->config->setAppValue(AbstractSecurityCheckProvider::CONFIG_DB_TYPE, BigDbPlusHibpSecurityCheckProvider::PASSWORD_DB);
        } else if($installed === HelperService::SECURITY_BIG_LOCAL) {
            $this->config->setAppValue(AbstractSecurityCheckProvider::CONFIG_DB_TYPE, BigLocalDbSecurityCheckProvider::PASSWORD_DB);
        } else if($installed === HelperService::SECURITY_SMALL_LOCAL) {
            $this->config->setAppValue(AbstractSecurityCheckProvider::CONFIG_DB_TYPE, SmallLocalDbSecurityCheckProvider::PASSWORD_DB);
        }

        $this->config->setAppValue(BigLocalDbSecurityCheckProvider::CONFIG_DB_VERSION, BigLocalDbSecurityCheckProvider::PASSWORD_VERSION);
        $output->writeln('Password database updated to v'.BigLocalDbSecurityCheckProvider::PASSWORD_VERSION);
    }

    /**
     * @param mixed    $file
     * @param callable $lineCallback
     *
     * @return void
     * @throws Exception
     */
    protected function readHashFile(mixed $file, callable $lineCallback): void {
        $handle = fopen($file, 'r');
        if(!$handle) {
            throw new Exception("Could not open file: {$file}");
        }

        while(($line = fgets($handle, 4096)) !== false) {
            if(empty($line)) {
                throw new Exception("File contains invalid lines");
            }

            [$sha1, $count] = explode(':', $line);
            $lineCallback($sha1, intval($count));
        }
        fclose($handle);
    }

    /**
     * @param array $occurrences
     * @param mixed $size
     *
     * @return array
     */
    protected function getMinimumPrevalence(array $occurrences, mixed $size): array {
        $minOccurrences = 0;

        $keys = array_keys($occurrences);
        rsort($keys, SORT_NUMERIC);
        $sizeLeft = $size * 1000000;
        foreach($keys as $key) {
            if($occurrences[ $key ] >= $sizeLeft) {
                return [$key, $sizeLeft];
            }

            $minOccurrences = $key;
            $sizeLeft       -= $occurrences[ $key ];
        }

        return [$minOccurrences, $occurrences[ $minOccurrences ]];
    }

    /**
     * @param string          $file
     * @param OutputInterface $output
     *
     * @return array
     * @throws Exception
     */
    protected function getHashPrevalence(string $file, OutputInterface $output): array {
        $output->writeln('Reading prevalence…');
        $progressBar = new ProgressBar($output, 4096);
        $progressBar->start();
        $progress = 'fff';

        $hashPrevalence = [];

        $this->readHashFile($file, function ($sha1, $count) use (&$hashPrevalence, &$progress, $progressBar) {
            if(isset($hashPrevalence[ $count ])) {
                $hashPrevalence[ $count ]++;
            } else {
                $hashPrevalence[ $count ] = 1;
            }

            if(!str_starts_with($sha1, $progress)) {
                $progress = substr($sha1, 0, 3);
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $output->writeln('');

        return $hashPrevalence;
    }

    /**
     * @param string $file                The file to read the hashes from
     * @param int    $minPrevalence       The minimum prevalence to include a hash
     * @param int    $minPrevalenceAmount The max amount of hashes with the $minPrevalence to be included
     *
     * @return string[]
     * @throws Exception
     */
    protected function getHashesByPrevalence(string $file, int $minPrevalence, int $minPrevalenceAmount, int $size, OutputInterface $output): array {
        $output->writeln('Reading hashes…');
        $progressBar = new ProgressBar($output, $size * 1000000);
        $progressBar->start();

        $hashes = [];
        $this->readHashFile($file, function ($sha1, $count) use (&$hashes, &$minPrevalenceAmount, $minPrevalence, $progressBar) {
            if($count > $minPrevalence || ($count === $minPrevalence && $minPrevalenceAmount > 0)) {
                if(strlen($sha1) !== 40) {
                    throw new Exception('Invalid SHA-1 hash encountered.');
                }

                if($count === $minPrevalence) {
                    $minPrevalenceAmount--;
                }

                $sha1  = strtolower($sha1);
                $start = substr($sha1, 0, AbstractSecurityCheckProvider::HASH_FILE_KEY_LENGTH);
                if(!isset($hashes[ $start ])) $hashes[ $start ] = [];
                $hashes[ $start ][] = $sha1;
                $progressBar->advance();
            }
        });
        $progressBar->finish();
        $output->writeln('');

        return $hashes;
    }
}