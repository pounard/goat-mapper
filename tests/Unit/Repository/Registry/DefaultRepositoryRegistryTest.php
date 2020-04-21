<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Repository\Registry;

use Goat\Mapper\Error\RepositoryDoesNotExistError;
use Goat\Mapper\Repository\AbstractRepository;
use Goat\Mapper\Repository\Repository;
use Goat\Mapper\Repository\Factory\RepositoryFactory;
use Goat\Mapper\Repository\Registry\DefaultRepositoryRegistry;
use PHPUnit\Framework\TestCase;

final class DefaultRepositoryRegistryTest extends TestCase
{
    public function testGetCalledOnlyOnceForHitsAndMisses(): void
    {
        $decorated = new class () implements RepositoryFactory {

            private $count = 0;

            public function getCount(): int
            {
                return $this->count;
            }

            public function createRepository(string $className): ?Repository
            {
                ++$this->count;

                if ('Foo' === $className) {
                    return new class () extends AbstractRepository {
                        public function __construct()
                        {
                        }
                    };
                }

                return null;
            }
        };

        $repositoryRegistry = new DefaultRepositoryRegistry($decorated);

        self::assertSame(0, $decorated->getCount());

        $repository = $repositoryRegistry->getRepository('Foo');

        self::assertSame(1, $decorated->getCount());

        self::assertSame($repository, $repositoryRegistry->getRepository('Foo'));

        self::assertSame(1, $decorated->getCount());

        try {
            $repositoryRegistry->getRepository('Bar');
            self::fail();
        } catch (RepositoryDoesNotExistError $e) {
        }

        self::assertSame(2, $decorated->getCount());

        try {
            $repositoryRegistry->getRepository('Bar');
            self::fail();
        } catch (RepositoryDoesNotExistError $e) {
        }

        self::assertSame(2, $decorated->getCount());
    }
}
