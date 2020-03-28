<?php

declare(strict_types=1);

namespace Goat\Mapper\Hydration\Proxy;

use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\ProxyInterface;

class ProxyFactory
{
    private LazyLoadingValueHolderFactory $proxyFactory;

    public function __construct(?LazyLoadingValueHolderFactory $proxyFactory = null)
    {
        $this->proxyFactory = $proxyFactory ?? new LazyLoadingValueHolderFactory();
    }

    public function getProxy(string $className, callable $loader): ProxyInterface
    {
        return $this
            ->proxyFactory
            ->createProxy(
                $className,
                $this->createInitializer($className, $loader)
            )
        ;
    }

    private function createInitializer(string $className, callable $loader): callable
    {
        return static function (
            &$wrappedObject,
            $proxy,
            $method,
            array $parameters,
            &$initializer
        ) use (
            $loader
        ): bool {
            $initializer = null;
            $wrappedObject = $loader();

            return true;
        };
    }
}
