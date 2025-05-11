<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Utils\Rector\Rector\PhpRandomizerFallback;
use Utils\Rector\Rector\RemoveTypeFromConst;

require_once '.rector/PhpRandomizerFallback.php';
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
                       php80: true,
                       php81: true,
                       php82: true,
                   )
                   ->withPhpVersion(PhpVersion::PHP_80)
                   ->withBootstrapFiles([__DIR__.'/rector-shells.php',])
                   ->withImportNames(removeUnusedImports: true)
                   ->withRules(
                       [
                           PhpRandomizerFallback::class,
                           RemoveTypeFromConst::class
                       ]
                   );