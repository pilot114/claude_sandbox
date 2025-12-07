<?php

declare(strict_types=1);

namespace Jirafik\Client;

final readonly class Worklog
{
    public function __construct(
        public string $id,
        public string $author,
        public ?string $authorAvatar,
        public string $timeSpent,
        public int $timeSpentSeconds,
        public ?string $started,
        public string $comment = '',
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'authorAvatar' => $this->authorAvatar,
            'timeSpent' => $this->timeSpent,
            'timeSpentSeconds' => $this->timeSpentSeconds,
            'started' => $this->started,
            'comment' => $this->comment,
        ];
    }
}
