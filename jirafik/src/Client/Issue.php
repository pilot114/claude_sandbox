<?php

declare(strict_types=1);

namespace Jirafik\Client;

final readonly class Issue
{
    /**
     * @param array<Comment> $comments
     * @param array<Attachment> $attachments
     * @param array<Worklog> $worklogs
     * @param array<string> $labels
     */
    public function __construct(
        public string $key,
        public string $id,
        public string $summary,
        public string $status,
        public string $statusCategory,
        public string $priority,
        public string $assignee,
        public ?string $assigneeAvatar,
        public string $reporter,
        public ?string $created,
        public ?string $updated,
        public ?string $dueDate,
        public array $labels,
        public string $issueType,
        public ?string $issueTypeIcon,
        public int $originalEstimate,
        public int $remainingEstimate,
        public int $timeSpent,
        public ?string $originalEstimateFormatted,
        public ?string $remainingEstimateFormatted,
        public ?string $timeSpentFormatted,
        public ?string $description = null,
        public array $comments = [],
        public array $attachments = [],
        public array $worklogs = [],
    ) {}

    public function toArray(bool $detailed = false): array
    {
        $result = [
            'key' => $this->key,
            'id' => $this->id,
            'summary' => $this->summary,
            'status' => $this->status,
            'statusCategory' => $this->statusCategory,
            'priority' => $this->priority,
            'assignee' => $this->assignee,
            'assigneeAvatar' => $this->assigneeAvatar,
            'reporter' => $this->reporter,
            'created' => $this->created,
            'updated' => $this->updated,
            'dueDate' => $this->dueDate,
            'labels' => $this->labels,
            'issueType' => $this->issueType,
            'issueTypeIcon' => $this->issueTypeIcon,
            'originalEstimate' => $this->originalEstimate,
            'remainingEstimate' => $this->remainingEstimate,
            'timeSpent' => $this->timeSpent,
            'originalEstimateFormatted' => $this->originalEstimateFormatted,
            'remainingEstimateFormatted' => $this->remainingEstimateFormatted,
            'timeSpentFormatted' => $this->timeSpentFormatted,
        ];

        if ($detailed) {
            $result['description'] = $this->description;
            $result['comments'] = array_map(fn(Comment $c) => $c->toArray(), $this->comments);
            $result['attachments'] = array_map(fn(Attachment $a) => $a->toArray(), $this->attachments);
            $result['worklogs'] = array_map(fn(Worklog $w) => $w->toArray(), $this->worklogs);
        }

        return $result;
    }
}
