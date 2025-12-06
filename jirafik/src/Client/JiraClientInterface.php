<?php

declare(strict_types=1);

namespace Jirafik\Client;

interface JiraClientInterface
{
    /**
     * Search issues by JQL query
     *
     * @param string $jql JQL query string
     * @param int $startAt Starting index for pagination
     * @param int $maxResults Maximum results to return
     * @param array<string> $fields Fields to retrieve
     * @return SearchResult
     */
    public function search(
        string $jql,
        int $startAt = 0,
        int $maxResults = 50,
        array $fields = []
    ): SearchResult;

    /**
     * Get single issue by key
     *
     * @param string $key Issue key (e.g., "PROJ-123")
     * @param array<string> $expand Fields to expand
     * @return Issue
     */
    public function getIssue(string $key, array $expand = []): Issue;

    /**
     * Add comment to issue
     *
     * @param string $key Issue key
     * @param string $body Comment text
     * @return CommentResult
     */
    public function addComment(string $key, string $body): CommentResult;

    /**
     * Add worklog entry to issue
     *
     * @param string $key Issue key
     * @param string $timeSpent Time spent (e.g., "2h", "30m")
     * @param string $comment Optional comment
     * @param string|null $started Start datetime (ISO 8601)
     * @return WorklogResult
     */
    public function addWorklog(
        string $key,
        string $timeSpent,
        string $comment = '',
        ?string $started = null
    ): WorklogResult;
}
