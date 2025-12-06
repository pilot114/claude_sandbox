<?php

declare(strict_types=1);

namespace Jirafik\Client;

final readonly class SearchResult
{
    /**
     * @param array<Issue> $issues
     */
    public function __construct(
        public int $total,
        public int $startAt,
        public int $maxResults,
        public array $issues,
    ) {}
}
