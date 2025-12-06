<?php

declare(strict_types=1);

namespace Jirafik\Client;

final readonly class Attachment
{
    public function __construct(
        public string $id,
        public string $filename,
        public int $size,
        public string $mimeType,
        public string $content,
        public ?string $thumbnail,
        public string $author,
        public ?string $created,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'size' => $this->size,
            'mimeType' => $this->mimeType,
            'content' => $this->content,
            'thumbnail' => $this->thumbnail,
            'author' => $this->author,
            'created' => $this->created,
        ];
    }
}
