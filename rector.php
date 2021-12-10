<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Rector\Set\ValueObject\DowngradeSetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PATHS, [
        __DIR__ . '/src/appinfo',
        __DIR__ . '/src/lib',
        __DIR__ . '/src/templates'
    ]);

    $containerConfigurator->import(DowngradeSetList::PHP_81);
    $containerConfigurator->import(DowngradeSetList::PHP_80);
    $containerConfigurator->import(DowngradeSetList::PHP_74);

    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_73);
    $parameters->set(Option::BOOTSTRAP_FILES, [
        __DIR__ . '/rector-shells.php',
    ]);
};