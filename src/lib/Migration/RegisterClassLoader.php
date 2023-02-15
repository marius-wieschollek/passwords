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

namespace OCA\Passwords\Migration;

use Exception;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RegisterClassLoader implements IRepairStep {

    /**
     *
     */
    public function __construct() {
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName() {
        return 'Register Class Loader';
    }

    /**
     * Run repair step.
     * Must throw exception on error.
     *
     * @param IOutput $output
     *
     * @throws Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output) {
        if(\OC_Util::getVersion()[0] < 26) {
            spl_autoload_register(
                function (string $class_name) {
                    if(str_starts_with($class_name, 'OCA\\Passwords')) {
                        $baseDir  = dirname(__FILE__, 2);
                        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, substr($class_name, 14)).'.php';
                        $path     = realpath(implode(DIRECTORY_SEPARATOR, [$baseDir, '.overrides', 'nc25', $fileName]));
                        if($path && str_starts_with($path, $baseDir) && \OC_Util::getVersion()[0] === 25) {
                            require_once $path;
                        }
                    }
                },
                true,
                true
            );
            if(function_exists('opcache_reset')) {
                opcache_reset();
            }
        }
    }
}