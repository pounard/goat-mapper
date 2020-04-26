<?php

declare(strict_types=1);

namespace Goat\Mapper\Tests\Sample;

use Goat\Mapper\Sample\Model\BlogPost;
use Goat\Mapper\Sample\Repository\DefaultBlogPostRepository;
use Goat\Runner\Runner;
use Goat\Runner\Testing\TestDriverFactory;

final class BlogPostTest extends AbstractSampleTest
{
    protected function injectTestData(Runner $runner, ?string $schema): void
    {
        $runner->execute(
            <<<SQL
            CREATE TABLE "blog_post" (
                "id" uuid NOT NULL,
                "author" varchar(500) NOT NULL,
                "created_at" timestamp NOT NULL DEFAULT current_timestamp,
                "updated_at"  timestamp NOT NULL DEFAULT current_timestamp,
                "title" varchar(500) NOT NULL,
                "content" text DEFAULT NULL,
                PRIMARY KEY ("id")
            )
            SQL
        );
    }

    /** @dataProvider runnerDataProvider */
    public function testSimpleScenario(TestDriverFactory $driverFactory): void
    {
        $runner = $driverFactory->getRunner(function (Runner $runner, ?string $schema) {
            $this->injectTestData($runner, $schema);
        });

        $entityManager = $this->createEntityManager($runner);

        /** @var \Goat\Mapper\Sample\Repository\BlogPostRepository $repository */
        $repository = $entityManager->getRepository(BlogPost::class);
        self::assertInstanceOf(DefaultBlogPostRepository::class, $repository);

        $blogPost = $repository->create('Foo?', 'Bar!', 'Robert');
        self::assertSame('Foo?', $blogPost->getTitle());
        self::assertSame('Bar!', $blogPost->getContent());
        self::assertSame('Robert', $blogPost->getAuthorName());

        $other = $repository->findOrDie($blogPost->getId());
        self::assertNotSame($blogPost, $other);
        self::assertTrue($other->getId()->equals($blogPost->getId()));

        $updated = $repository->updateContent($blogPost->getId(), 'Hello', 'World');
        self::assertSame('Hello', $updated->getTitle());
        self::assertSame('World', $updated->getContent());

        $repository->create('Paf', 'Pouf', 'Roger');

        $mostRecent = $repository->findMostRecentForAuthor('Robert');
        self::assertCount(1, $mostRecent);
        $found = 0;
        foreach ($mostRecent as $post) {
            ++$found;
            self::assertSame('Robert', $post->getAuthorName());
        }
        self::assertSame(1, $found);
    }
}
