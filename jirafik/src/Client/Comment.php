<?php

declare(strict_types=1);

namespace Jirafik\Client;

final readonly class Comment
{
    public function __construct(
        public string $id,
        public string $author,
        public ?string $authorAvatar,
        public string $body,
        public ?string $created,
        public ?string $updated = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'authorAvatar' => $this->authorAvatar,
            'body' => $this->body,
            'created' => $this->created,
            'updated' => $this->updated,
        ];
    }
}
