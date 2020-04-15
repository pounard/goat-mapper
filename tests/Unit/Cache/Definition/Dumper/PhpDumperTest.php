<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Unit\Cache\Definition\Dumper;

use Goat\Mapper\Cache\Definition\Dumper\PhpDumper;
use Goat\Mapper\Cache\Writer\MemoryWriter;
use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\WithManyToOneRelation;

final class PhpDumperTest extends AbstractRepositoryTest
{
    public function testStupid(): void
    {
        $writer = new MemoryWriter();
        $dumper = new PhpDumper();

        $manager = $this->createRepositoryManager();
        $definitionRegistry = $manager->getDefinitionRegistry();

        $dumper->dump(
            $definitionRegistry->getDefinition(WithManyToOneRelation::class),
            $writer
        );

        // \print_r("\n\n\n" . $writer->closeAndGetBuffer() . "\n\n\n");
    }
}
