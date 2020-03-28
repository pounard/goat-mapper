<?php

declare(strict_types=1);

namespace Goat\Mapper\Test\Query\Graph;

use Goat\Mapper\Query\Graph\EntityQuery;
use Goat\Mapper\Query\Graph\Traverser;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\Client;
use Goat\Runner\Testing\NullRunner;

final class EntityQueryTest extends AbstractRepositoryTest
{
    public function testGraphStopsAtToManyRelations(): void
    {
        self::markTestSkipped();

        $manager = $this->createRepositoryManager();

        $query = new EntityQuery(
            $manager->getDefinitionRegistry(),
            $manager->getRunner(),
            Client::class
        );

        $time = \microtime(true);
        $query->eager('addresses.country');
        $query->matches('addresses.country', 'fr');
        \var_dump('build graph: '.\round((\microtime(true) - $time) * 1000));

        $time = \microtime(true);
        $traverser = new Traverser();
        $traverser->traverse($query);
        \var_dump('traverse graph (build query): '.\round((\microtime(true) - $time) * 1000));

        $time = \microtime(true);
        \var_dump(
            (new NullRunner())
                ->getFormatter()
                ->format($query->getQuery())
        );

        \var_dump('format query: '.\round((\microtime(true) - $time) * 1000));
    }
}
