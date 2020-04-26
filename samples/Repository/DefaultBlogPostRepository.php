<?php

declare(strict_types=1);

namespace Goat\Mapper\Sample\Repository;

use Goat\Mapper\Repository\AbstractRepository;
use Goat\Mapper\Sample\Model\BlogPost;
use Goat\Query\Query;
use Goat\Runner\ResultIterator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class DefaultBlogPostRepository extends AbstractRepository implements BlogPostRepository
{
    /**
     * {@inheritdoc}
     */
    public function find(UuidInterface $id): ?BlogPost
    {
        // We do not use the goat-query query builder directly but the high
        // level API for querying data, it will have a minor CPU bound perf
        // impact, but it will set up the entity hydrator for eager loading
        // all entity relations that can be eagerly loaded, and setup the
        // lazy relation fetcher as well.
        return $this
            ->query()
            ->matches('id', $id)
            ->build()
            ->range(1)
            ->execute()
            ->fetch()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function findOrDie(UuidInterface $id): BlogPost
    {
        $ret = $this->find($id);

        if (!$ret) {
            throw new \InvalidArgumentException(\sprintf(
                "Blog post with id '%s' does not exist",
                $id->toString()
            ));
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function findMostRecentForAuthor(string $author, int $limit = 100): ResultIterator
    {
        return $this
            ->query()

            // Note that we could have set conditions on pretty any property
            // of the entity, match condition can target column names as well
            // if they differ from the property names.
            // You can also match related entities conditions, but this entity
            // has none for now.
            ->matches('author', $author)

            // Also take note that once the build() method is called, you get
            // the raw goat-query query instead, from that point, everything
            // you add will be raw SQL, you MUST target SQL column names and
            // not entity property anymore.
            ->build()
            ->orderBy('created_at', Query::ORDER_DESC)
            ->range($limit)
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $title, string $content, string $author = 'Anonymous'): BlogPost
    {
        $insert = $this
            ->getRunner()
            ->getQueryBuilder()
            ->insertValues(
                $this->getRelation()
            )
            ->values([
                'id' => Uuid::uuid4(),
                'title' => $title,
                'content' => $content,
                'author' => $author,
            ])
        ;

        // This will add a pgsql RETURNING clause on the query.
        $this->addQueryReturningClause($insert);

        // This will prepare the entity hydrator and set it on the query,
        // since we added a RETURNING clause, returned values will be used
        // as if they where a classical SELECT in order to hydrate returned
        // entity.
        $this->addQueryEntityHydrator($insert);

        return $insert->execute()->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function updateContent(UuidInterface $id, string $title, string $content): BlogPost
    {
        $insert = $this
            ->getRunner()
            ->getQueryBuilder()
            ->update(
                $this->getRelation()
            )
            ->condition('id', $id)
            ->set('title', $title)
            ->set('content', $content)
        ;

        // See commends in create() method.
        $this->addQueryReturningClause($insert);
        $this->addQueryEntityHydrator($insert);

        return $insert->execute()->fetch();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(UuidInterface $id): BlogPost
    {
        $delete = $this
            ->getRunner()
            ->getQueryBuilder()
            ->delete(
                $this->getRelation()
            )
            ->condition('id', $id)
        ;

        // See commends in create() method.
        $this->addQueryReturningClause($delete);
        $this->addQueryEntityHydrator($delete);

        return $delete->execute()->fetch();
    }
}
