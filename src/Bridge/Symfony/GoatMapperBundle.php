<?php

declare(strict_types=1);

namespace Goat\Mapper\Bridge\Symfony;

use Goat\Mapper\Bridge\Symfony\DependencyInjection\GoatMapperExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class GoatMapperBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new GoatMapperExtension();
    }
}
