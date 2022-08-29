<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\ClassMethod\OptionalParametersAfterRequiredRector;
use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src'
    ]);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
    ]);

    $rectorConfig->importNames();

    $rectorConfig->skip([
        // These files trigger errors
        __DIR__ . '/src/Everest/Http/RoutingContext.php',

        OptionalParametersAfterRequiredRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        CountOnNullRector::class,

        // Doctrine Proxies
        __DIR__ . '/src/IC3/Infrastructure/Doctrine/Proxies',
    ]);
};
