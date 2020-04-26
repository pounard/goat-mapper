<?php

declare(strict_types=1);

namespace Goat\Mapper\Sample\Repository;

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
    public function create(string $title, string $content, string $author = 'Anonymous'): BlogPost;

    /**
     * Update blog post and return updated instance.
     */
    public function updateContent(UuidInterface $id, string $title, string $content): BlogPost;

    /**
     * Delete blog post entry and return deleted instance.
     */
    public function delete(UuidInterface $id): BlogPost;
}
