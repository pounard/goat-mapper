<?php

declare(strict_types=1);

use GeneratedHydrator\Bridge\Symfony\DefaultHydrator;
use Goat\Driver\Configuration;
use Goat\Driver\ExtPgSQLDriver;
use Goat\Mapper\DefaultEntityManager;
use Goat\Mapper\Cache\Definition\Registry\PhpDefinitionRegistry;
use Goat\Mapper\Definition\Registry\CacheDefinitionRegistry;
use Goat\Mapper\Definition\Registry\ChainDefinitionRegistry;
use Goat\Mapper\Definition\Registry\StaticEntityDefinitionRegistry;
use Goat\Mapper\Hydration\EntityHydrator\EntityHydratorFactory;
use Goat\Mapper\Hydration\HydratorRegistry\GeneratedHydratorBundleHydratorRegistry;
use Goat\Mapper\Repository\Factory\ChainRepositoryFactory;
use Goat\Mapper\Repository\Factory\DefaultRepositoryFactory;
use Goat\Mapper\Sample\Repository\Factory\MyApplicationRepositoryFactory;
use Goat\Mapper\Repository\Registry\DefaultRepositoryRegistry;

// Definition registry

$chainDefinitionRegistry = new ChainDefinitionRegistry();
$phpDefinitionRegistry = new PhpDefinitionRegistry($chainDefinitionRegistry);
$definitionRegistry = new CacheDefinitionRegistry($phpDefinitionRegistry);

$phpDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);

$staticDefinitionRegistry = new StaticEntityDefinitionRegistry();
$staticDefinitionRegistry->setParentDefinitionRegistry($definitionRegistry);
$chainDefinitionRegistry->add($staticDefinitionRegistry);

// Entity hydrator

$entityHydrator = new EntityHydratorFactory(
    $definitionRegistry,
    new GeneratedHydratorBundleHydratorRegistry(
        new DefaultHydrator(
            \sys_get_temp_dir()
        )
    )
);

// Database connection

if (!isset($runner)) {
    $driver = new ExtPgSQLDriver();
    $driver->setConfiguration(
        Configuration::fromString(
            "pgsql://user:password@hostname:port/database"
        )
    );
    $runner = $driver->getRunner();
}

// Repository registry

$chainRepositoryFactory = new ChainRepositoryFactory();
$repositoryRegistry = new DefaultRepositoryRegistry($chainRepositoryFactory);

$defaultRepositoryFactory = new DefaultRepositoryFactory();
$customRepositoryFactory = new MyApplicationRepositoryFactory();

$chainRepositoryFactory->add($customRepositoryFactory);
$chainRepositoryFactory->add($defaultRepositoryFactory);

// Entity manager

$entityManager = new DefaultEntityManager(
    $runner,
    $definitionRegistry,
    $entityHydrator,
    $repositoryRegistry
);

$customRepositoryFactory->setEntityManager($entityManager);
$defaultRepositoryFactory->setEntityManager($entityManager);
