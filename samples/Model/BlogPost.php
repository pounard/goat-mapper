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
        return $this->content;
    }
}
