<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\ValueObject\PhpVersion;
use Utils\Rector\Rector\RemoveTypeFromConst;

require_once '.rector/RemoveTypeFromConst.php';

return RectorConfig::configure()
                   ->withPaths(
                       [
                           __DIR__.'/src/appinfo',
                           __DIR__.'/src/lib',
                           __DIR__.'/src/templates'
                       ]
                   )
                   ->withDowngradeSets(
                       php80: true
                   )
                   ->withPhpVersion(PhpVersion::PHP_80)
                   ->withBootstrapFiles([__DIR__.'/rector-shells.php',])
                   ->withImportNames(removeUnusedImports: true)
                   ->withConfiguredRule(RenameClassRector::class, [
                       'Random\Randomizer'      => 'OCA\Passwords\Compatibility\Random\Randomizer',
                       'OCP\ServerVersion'      => 'OCA\Passwords\Compatibility\OCP\ServerVersion',
                       'OCP\Config\IUserConfig' => 'OCA\Passwords\Compatibility\OCP\Config\UserConfig',
                       'OC\Avatar\GuestAvatar'  => 'OCA\Passwords\Compatibility\OC\Avatar\GuestAvatar',
                   ])
                   ->withSkip([RenameClassRector::class => [__DIR__.'/src/lib/Compatibility']])
                   ->withRules(
                       [
                           RemoveTypeFromConst::class
                       ]
                   );