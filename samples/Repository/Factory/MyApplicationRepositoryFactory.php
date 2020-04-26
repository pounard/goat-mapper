<?php

declare(strict_types=1);

namespace Goat\Mapper\Sample\Repository\Factory;

use Goat\Mapper\Repository\Repository;
use Goat\Mapper\Repository\Factory\AbstractRepositoryFactory;
use Goat\Mapper\Sample\Model\BlogPost;
use Goat\Mapper\Sample\Repository\DefaultBlogPostRepository;

final class MyApplicationRepositoryFactory extends AbstractRepositoryFactory
{
    public function createRepository(string $className): ?Repository
    {
        if (BlogPost::class === $className) {
            return new DefaultBlogPostRepository($className, $this->getEntityManager());
        }

        // You may support more than one class, you may add some here:
        //
        // if (Author::class === $className) {
        //     return new DefaultAuthorRepository($className, $this->getEntityManager());
        // }
        //
        // ...

        return null;
    }
}
