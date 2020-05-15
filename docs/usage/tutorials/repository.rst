.. _repository:

Repository
==========

Repository is a classic persistence pattern, this API doesn't aim to implement
those for you, but gives you a basic tooling for getting started with them,
register them, and tie them with the query builder.

This tutorial will show you how to:

 - define a custom entity,
 - define a repository interface for this entity,
 - implement the repository interface using the abstract repository
   implementation,
 - register your custom repository using a repository factory,
 - optionally, instead of using a custom repository factory, use the
   Symfony bundle container to register it automatically,
 - fetch the repository instance and use it.

During this whole tutorial, consider ``Goat\Mapper\Sample`` to be your own
``Vendor\Application`` namespace.

.. note::

   You can find the working code in the ``samples/`` directory.

Define your entity
^^^^^^^^^^^^^^^^^^

There are mutiple ways to define an entity, for the sake of example we will
proceed with the simple way to do it, using the static "definition builder" (@todo link).

.. note::

   Please see the "Define entities" (@todo link) section for more options on how to define
   an entity.

First we will start with the following SQL table:

.. code-block:: sql

   CREATE TABLE "blog_post" (
       "id" uuid NOT NULL,
       "author" varchar(500) NOT NULL,
       "created_at" timestamp NOT NULL DEFAULT current_timestamp,
       "updated_at"  timestamp NOT NULL DEFAULT current_timestamp,
       "title" varchar(500) NOT NULL,
       "content" text DEFAULT NULL,
       PRIMARY KEY ("id")
   );

For the same of keeping the example simple, we will not add foreign keys at the
begining.

Define your PHP entity class as such:

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Goat\Mapper\Sample\Model;

   use Ramsey\Uuid\Uuid;
   use Ramsey\Uuid\UuidInterface;

   class BlogPost
   {
       private ?UuidInterface $id = null;
       private string $author;
       private \DateTimeInterface $createdAt;
       private \DateTimeInterface $updatedAt;
       private string $title;
       private ?string $content = null;

       public function getId(): UuidInterface
       {
           return $this->id ?? ($this->id = Uuid::uuid4());
       }

       public function getAuthorName(): string
       {
           return $this->author;
       }

       public function getCreationDate(): \DateTimeInterface
       {
           return $this->createdAt;
       }

       public function getUpdateDate(): \DateTimeInterface
       {
           return $this->updatedAt;
       }

       public function getTitle(): string
       {
           return $this->title;
       }

       public function getContent(): ?string
       {
           return $this->body;
       }
   }

Then add the ``Goat\Mapper\Definition\Registry\StaticEntityDefinition``
interface and implementation:

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Goat\Mapper\Sample\Model;

   use Goat\Mapper\Definition\Builder\DefinitionBuilder;
   use Goat\Mapper\Definition\Registry\StaticEntityDefinition;
   use Ramsey\Uuid\Uuid;
   use Ramsey\Uuid\UuidInterface;

   class BlogPost implements StaticEntityDefinition
   {
       private ?UuidInterface $id = null;
       private string $author;
       private \DateTimeInterface $createdAt;
       private \DateTimeInterface $updatedAt;
       private string $title;
       private ?string $content = null;

       /**
        * {@inheritdoc}
        */
       public static function defineEntity(DefinitionBuilder $builder): void
       {
           $builder->setTableName('blog_post');
           $builder->addProperty('id');
           $builder->addProperty('author');
           $builder->addProperty('createdAt', 'created_at');
           $builder->addProperty('updatedAt', 'updated_at');
           $builder->addProperty('title');
           $builder->addProperty('content');
           $builder->setPrimaryKey([
               'id' => 'uuid'
           ]);
       }

       public function getId(): UuidInterface
       {
           return $this->id ?? ($this->id = Uuid::uuid4());
       }

       public function getAuthorName(): string
       {
           return $this->author;
       }

       public function getCreationDate(): \DateTimeInterface
       {
           return $this->createdAt;
       }

       public function getUpdateDate(): \DateTimeInterface
       {
           return $this->updatedAt;
       }

       public function getTitle(): string
       {
           return $this->title;
       }

       public function getContent(): ?string
       {
           return $this->body;
       }
   }

Now we have an automatically-registered entity, you can already start using
the query builder to load and hydrate those from database.

Define your repository interface
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Next step is defining what are the methods your domain need for persisting your
entities, let's arbitrary choose some for the sake of example:

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Goat\Mapper\Sample\Model;

   use Goat\Mapper\Sample\Model\BlogPost;
   use Goat\Runner\ResultIterator;
   use Ramsey\Uuid\UuidInterface;

   interface BlogPostRepository
   {
       /**
        * Find a single blog post.
        */
       public function find(UuidInterface $id): ?BlogPost;

       /**
        * Find a single blog post or die.
        */
       public function findOrDie(UuidInterface $id): BlogPost;

       /**
        * Find entries by author.
        *
        * @return BlogPost[]
        */
       public function findMostRecentForAuthor(string $author): ResultIterator;

       /**
        * Create new blog post.
        */
       public function create(string $title, string $content, ?string $author = 'Anonymous'): BlogPost;

       /**
        * Update blog post and return updated instance.
        */
       public function updateContent(UuidInterface $id, string $title, string $content): BlogPost;

       /**
        * Delete blog post entry and return deleted instance.
        */
       public function delete(UuidInterface $id): BlogPost;
   }

Implement your repository interface
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Class declaration
#################

Start by the class declaration. In order to be able to implement this repository
easily, you may use the ``Goat\Mapper\Repository\AbstractRepository`` abstract
class as base.

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Goat\Mapper\Sample\Model;

   use Goat\Mapper\Sample\Model\BlogPost;
   use Goat\Mapper\Repository\AbstractRepository;
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
           throw new \Exception("Not implemented yet.");
       }

       /**
        * {@inheritdoc}
        */
       public function findOrDie(UuidInterface $id): BlogPost
       {
           throw new \Exception("Not implemented yet.");
       }

       /**
        * {@inheritdoc}
        */
       public function findMostRecentForAuthor(string $author, int $limit = 100): ResultIterator
       {
           throw new \Exception("Not implemented yet.");
       }

       /**
        * {@inheritdoc}
        */
       public function create(string $title, string $content, string $author = 'Anonymous'): BlogPost
       {
           throw new \Exception("Not implemented yet.");
       }

       /**
        * {@inheritdoc}
        */
       public function updateContent(UuidInterface $id, string $title, string $content): BlogPost
       {
           throw new \Exception("Not implemented yet.");
       }

       /**
        * {@inheritdoc}
        */
       public function delete(UuidInterface $id): BlogPost
       {
           throw new \Exception("Not implemented yet.");
       }
   }

Now we can implement repository methods.

Read methods
############

@todo

Write methods
#############

@todo

Wrapping up
###########

Here is the complete class implementation:

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Goat\Mapper\Sample\Model;

   use Goat\Mapper\Sample\Model\BlogPost;
   use Goat\Mapper\Repository\AbstractRepository;
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
               ->range(0, 1)
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

Register your repository
^^^^^^^^^^^^^^^^^^^^^^^^

@todo
