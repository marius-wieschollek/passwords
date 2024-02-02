<?php

declare(strict_types=1);

use Rector\ValueObject\PhpVersion;
use Rector\Set\ValueObject\DowngradeSetList;
use Rector\Config\RectorConfig;

require_once '.rector/RemoveRandomizerClassInstantiation.php';

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths(
        [
            __DIR__.'/src/appinfo',
            __DIR__.'/src/lib',
            __DIR__.'/src/templates'
        ]
    );

    $rectorConfig->sets(
        [
            DowngradeSetList::PHP_82,
            DowngradeSetList::PHP_81
        ]
    );

    $rectorConfig->phpVersion(PhpVersion::PHP_80);
    $rectorConfig->bootstrapFiles([__DIR__.'/rector-shells.php',]);
    $rectorConfig->removeUnusedImports(true);

    $rectorConfig->rule(\Utils\Rector\Rector\PhpRandomizerFallback::class);
};