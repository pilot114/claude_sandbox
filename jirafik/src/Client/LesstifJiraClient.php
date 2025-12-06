<?php

declare(strict_types=1);

namespace Jirafik\Client;

use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Comment as JiraComment;
use JiraRestApi\Issue\Worklog as JiraWorklog;

/**
 * Adapter for lesstif/php-jira-rest-client library
 */
final class LesstifJiraClient implements JiraClientInterface
{
    private IssueService $issueService;

    public function __construct(?IssueService $issueService = null)
    {
        $this->issueService = $issueService ?? new IssueService();
    }

    public function search(
        string $jql,
        int $startAt = 0,
        int $maxResults = 50,
        array $fields = []
    ): SearchResult {
        $result = $this->issueService->search($jql, $startAt, $maxResults, $fields);

        $issues = array_map(
            fn(object $issue) => $this->mapIssue($issue),
            $result->issues
        );

        return new SearchResult(
            total: $result->total,
            startAt: $result->startAt,
            maxResults: $result->maxResults,
            issues: $issues,
        );
    }

    public function getIssue(string $key, array $expand = []): Issue
    {
        $options = $expand ? ['expand' => implode(',', $expand)] : [];
        $issue = $this->issueService->get($key, $options);

        return $this->mapIssue($issue, detailed: true);
    }

    public function addComment(string $key, string $body): CommentResult
    {
        $comment = new JiraComment();
        $comment->setBody($body);

        $result = $this->issueService->addComment($key, $comment);

        return new CommentResult(
            id: $result->id,
            author: $result->author->displayName ?? 'Unknown',
            body: $result->body,
            created: $result->created,
        );
    }

    public function addWorklog(
        string $key,
        string $timeSpent,
        string $comment = '',
        ?string $started = null
    ): WorklogResult {
        $worklog = new JiraWorklog();
        $worklog->setComment($comment)
                ->setStarted($started ?? date('Y-m-d\TH:i:s.000O'))
                ->setTimeSpent($timeSpent);

        $result = $this->issueService->addWorklog($key, $worklog);

        return new WorklogResult(
            id: $result->id,
            author: $result->author->displayName ?? 'Unknown',
            timeSpent: $result->timeSpent,
            timeSpentSeconds: $result->timeSpentSeconds,
            started: $result->started,
            comment: $result->comment ?? '',
        );
    }

    private function mapIssue(object $issue, bool $detailed = false): Issue
    {
        $fields = $issue->fields;

        $comments = [];
        $attachments = [];
        $worklogs = [];

        if ($detailed) {
            $comments = array_map(
                fn(object $c) => new Comment(
                    id: $c->id,
                    author: $c->author->displayName ?? 'Unknown',
                    authorAvatar: $c->author->avatarUrls->{'48x48'} ?? null,
                    body: $c->body ?? '',
                    created: $c->created ?? null,
                    updated: $c->updated ?? null,
                ),
                $fields->comment->comments ?? []
            );

            $attachments = array_map(
                fn(object $a) => new Attachment(
                    id: $a->id,
                    filename: $a->filename,
                    size: $a->size,
                    mimeType: $a->mimeType,
                    content: $a->content,
                    thumbnail: $a->thumbnail ?? null,
                    author: $a->author->displayName ?? 'Unknown',
                    created: $a->created ?? null,
                ),
                $fields->attachment ?? []
            );

            $worklogs = array_map(
                fn(object $w) => new Worklog(
                    id: $w->id,
                    author: $w->author->displayName ?? 'Unknown',
                    authorAvatar: $w->author->avatarUrls->{'48x48'} ?? null,
                    timeSpent: $w->timeSpent ?? '',
                    timeSpentSeconds: $w->timeSpentSeconds ?? 0,
                    started: $w->started ?? null,
                    comment: $w->comment ?? '',
                ),
                $fields->worklog->worklogs ?? []
            );
        }

        return new Issue(
            key: $issue->key,
            id: $issue->id,
            summary: $fields->summary ?? '',
            status: $fields->status->name ?? 'Unknown',
            statusCategory: $fields->status->statusCategory->key ?? 'undefined',
            priority: $fields->priority->name ?? 'None',
            assignee: $fields->assignee->displayName ?? 'Unassigned',
            assigneeAvatar: $fields->assignee->avatarUrls->{'48x48'} ?? null,
            reporter: $fields->reporter->displayName ?? 'Unknown',
            created: $fields->created ?? null,
            updated: $fields->updated ?? null,
            dueDate: $fields->duedate ?? null,
            labels: $fields->labels ?? [],
            issueType: $fields->issuetype->name ?? 'Task',
            issueTypeIcon: $fields->issuetype->iconUrl ?? null,
            originalEstimate: $fields->timeoriginalestimate ?? 0,
            remainingEstimate: $fields->timeestimate ?? 0,
            timeSpent: $fields->timespent ?? 0,
            originalEstimateFormatted: $fields->timetracking->originalEstimate ?? null,
            remainingEstimateFormatted: $fields->timetracking->remainingEstimate ?? null,
            timeSpentFormatted: $fields->timetracking->timeSpent ?? null,
            description: $detailed ? ($fields->description ?? '') : null,
            comments: $comments,
            attachments: $attachments,
            worklogs: $worklogs,
        );
    }
}
