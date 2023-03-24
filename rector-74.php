<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\DowngradeSetList;
use Rector\Config\RectorConfig;

require_once ".rector/RemovePureAnnotation.php";
require_once ".rector/FixReturnTypeExtension.php";

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
            DowngradeSetList::PHP_81,
            DowngradeSetList::PHP_80
        ]
    );

    $rectorConfig->phpVersion(PhpVersion::PHP_74);
    $rectorConfig->bootstrapFiles([__DIR__.'/rector-shells.php',]);

    $rectorConfig->rule(\Utils\Rector\Rector\RemovePureAnnotation::class);
    $rectorConfig->rule(\Utils\Rector\Rector\FixReturnTypeExtension::class);
};