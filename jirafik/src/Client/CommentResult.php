<?php

declare(strict_types=1);

namespace Jirafik\Client;

final readonly class CommentResult
{
    public function __construct(
        public string $id,
        public string $author,
        public string $body,
        public ?string $created,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'body' => $this->body,
            'created' => $this->created,
        ];
    }
}
