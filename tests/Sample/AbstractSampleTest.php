<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Sample;

use Goat\Mapper\EntityManager;
use Goat\Mapper\Sample\Model\BlogPost;
use Goat\Runner\Runner;
use Goat\Runner\Testing\DatabaseAwareQueryTest;

abstract class AbstractSampleTest extends DatabaseAwareQueryTest
{
    final public static function getTestEntityClasses(): array
    {
        return [
            BlogPost::class,
        ];
    }

    final protected function createEntityManager(?Runner $runner = null): EntityManager
    {
        $entityManager = null;
        include \dirname(__DIR__, 2) . '/samples/bootstrap.php';

        \assert($entityManager instanceof EntityManager);

        return $entityManager;
    }

    final protected function createSchema(Runner $runner, ?string $schema): void
    {
        $driverName = $runner->getDriverName();

        foreach (self::getTestEntityClasses() as $className) {
            if (\method_exists($className, 'toTableSchema')) {
                $tableSchema = \call_user_func([$className, 'toTableSchema'], $schema);

                // Cast as array there might be more than one statement.
                $statements = (array)($tableSchema[$driverName] ?? $tableSchema['default']);

                foreach ($statements as $statement) {
                    $runner->execute($statement);
                }
            }
        }
    }
}
