<?php

declare(strict_types=1);

namespace Jirafik\Client;

final readonly class WorklogResult
{
    public function __construct(
        public string $id,
        public string $author,
        public string $timeSpent,
        public int $timeSpentSeconds,
        public ?string $started,
        public string $comment = '',
    ) {}
}
