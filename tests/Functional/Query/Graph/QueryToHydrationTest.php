<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Functional\Query\Graph;

use Goat\Mapper\Tests\AbstractRepositoryTest;
use Goat\Mapper\Tests\Mock\Client;
use Goat\Runner\Runner;
use Goat\Runner\Testing\TestDriverFactory;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class QueryToHydrationTest extends AbstractRepositoryTest
{
    private ?UuidInterface $clientId = null;

    protected function setUp()
    {
        parent::setUp();

        $this->clientId = Uuid::uuid4();
    }

    protected function injectTestData(Runner $runner, ?string $schema): void
    {
        $this->createSchema($runner, $schema);

        $runner
            ->getQueryBuilder()
            ->insertValues('client')
            ->columns([
                'id',
                'firstname',
                'lastname',
            ])
            ->values([
                Uuid::uuid4(),
                'Robert',
                'Doisneau',
            ])
            ->values([
                $this->clientId,
                'Josianne',
                'Balasko',
            ])
            ->values([
                Uuid::uuid4(),
                'Bernard',
                'Minet',
            ])
            ->execute()
        ;

        $runner
            ->getQueryBuilder()
            ->insertValues('country_list')
            ->columns([
                'code',
                'title',
            ])
            ->values([
                'fr',
                'France',
            ])
            ->values([
                'es',
                'España',
            ])
            ->execute()
        ;

        $runner
            ->getQueryBuilder()
            ->insertValues('client_address')
            ->columns([
                'id',
                'client_id',
                'type',
                'line1',
                'line2',
                'locality',
                'zipcode',
                'country',
            ])
            ->values([
                Uuid::uuid4(),
                'client_id' => $this->clientId,
                'type' => 'shipping',
                'line1' => 'Rue de la fontaine de Barbin',
                'line2' => null,
                'locality' => 'Nantes',
                'zipcode' => '44000',
                'country' => 'fr',
            ])
            ->values([
                Uuid::uuid4(),
                'client_id' => $this->clientId,
                'type' => 'work',
                'line1' => 'Rue des jeneurs',
                'line2' => null,
                'locality' => 'Paris',
                'zipcode' => '75000',
                'country' => 'fr',
            ])
            ->values([
                Uuid::uuid4(),
                'client_id' => $this->clientId,
                'type' => 'vacations',
                'line1' => 'Calle bella vida',
                'line2' => null,
                'locality' => 'Madrid',
                'zipcode' => '75000',
                'country' => 'es',
            ])
            ->execute()
        ;
    }

    /** @dataProvider runnerDataProvider */
    public function testLazyLoadSelect(TestDriverFactory $driverFactory): void
    {
        $runner = $driverFactory->getRunner(function (Runner $runner, ?string $schema) {
            $this->injectTestData($runner, $schema);
        });

        $manager = $this->createRepositoryManager($runner);

        $clientRepository = $manager->getRepository(Client::class);
        $entity = $clientRepository
            ->query()
            ->eager('addresses')
            ->matches('id', $this->clientId)
            ->execute()
            ->fetch()
        ;

        \assert($entity instanceof Client);
        self::assertSame('Josianne', $entity->getFirstname());

        $addresses = $entity->getAddresses();
        self::assertCount(3, $addresses);
    }

    /** @dataProvider runnerDataProvider */
    public function testEagerLoadSelect(TestDriverFactory $driverFactory): void
    {
        $runner = $driverFactory->getRunner(function (Runner $runner, ?string $schema) {
            $this->injectTestData($runner, $schema);
        });

        $manager = $this->createRepositoryManager($runner);

        $clientRepository = $manager->getRepository(Client::class);
        $entity = $clientRepository
            ->query()
            ->eager('addresses')
            ->matches('id', $this->clientId)
            ->execute()
            ->fetch()
        ;

        \assert($entity instanceof Client);
        self::assertSame('Josianne', $entity->getFirstname());

        $addresses = $entity->getAddresses();
        self::assertCount(3, $addresses);
    }

    /** @dataProvider runnerDataProvider */
    public function testBasicSelect(TestDriverFactory $driverFactory): void
    {
        $runner = $driverFactory->getRunner(function (Runner $runner, ?string $schema) {
            $this->injectTestData($runner, $schema);
        });

        $manager = $this->createRepositoryManager($runner);

        $clientRepository = $manager->getRepository(Client::class);
        $entities = $clientRepository
            ->query()
            ->build()
            ->orderBy('firstname')
            ->execute()
        ;

        self::assertCount(3, $entities);

        $entity = $entities->fetch();
        \assert($entity instanceof Client);
        self::assertSame('Bernard', $entity->getFirstname());

        $entity = $entities->fetch();
        \assert($entity instanceof Client);
        self::assertSame('Josianne', $entity->getFirstname());

        $entity = $entities->fetch();
        \assert($entity instanceof Client);
        self::assertSame('Robert', $entity->getFirstname());
    }
}
