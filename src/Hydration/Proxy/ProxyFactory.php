<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\Proxy;

use Goat\Mapper\Repository;
use Goat\Mapper\Definition\Identifier;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\ProxyInterface;

class ProxyFactory
{
    /** @var array<string,string> */
    private $proxyClasses = [];

    /** @var LazyLoadingValueHolderFactory */
    private $proxyFactory;

    public function __construct(?LazyLoadingValueHolderFactory $proxyFactory = null)
    {
        $this->proxyFactory = $proxyFactory ?? new LazyLoadingValueHolderFactory();
    }

    public function getProxy(Repository $repository, Identifier $identifier): ProxyInterface
    {
        return $this
            ->proxyFactory
            ->createProxy(
                $repository
                    ->getRepositoryDefinition()
                    ->getEntityDefinition()
                    ->getClassName(),
                $this->createInitializer(
                    $repository,
                    $identifier
                )
            )
        ;
    }

    private function createInitializer(Repository $repository, Identifier $identifier): callable
    {
        return static function (
            &$wrappedObject,
            $proxy,
            $method,
            array $parameters,
            &$initializer
        ) use (
            $repository,
            $identifier
        ): bool {
            $initializer = null;
            $wrappedObject = $repository->findOne($identifier);

            return true;
        };
    }
}
