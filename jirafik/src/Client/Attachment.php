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
}
