<?php

declare(strict_types=1);

namespace Jirafik\Client;

/**
 * Native PHP implementation without external dependencies
 * Uses cURL for HTTP requests to Jira REST API
 */
final class NativeJiraClient implements JiraClientInterface
{
    private string $baseUrl;
    private string $authHeader;

    public function __construct(
        string $host,
        string $user,
        string $token,
    ) {
        $this->baseUrl = rtrim($host, '/') . '/rest/api/2';
        $this->authHeader = 'Basic ' . base64_encode("{$user}:{$token}");
    }

    public static function fromEnv(): self
    {
        $host = $_ENV['JIRA_HOST'] ?? getenv('JIRA_HOST') ?: '';
        $user = $_ENV['JIRA_USER'] ?? getenv('JIRA_USER') ?: '';
        $token = $_ENV['JIRA_PASS'] ?? getenv('JIRA_PASS') ?: '';

        if ($host === '' || $user === '' || $token === '') {
            throw new JiraException('Missing JIRA_HOST, JIRA_USER or JIRA_PASS environment variables');
        }

        return new self($host, $user, $token);
    }

    public function search(
        string $jql,
        int $startAt = 0,
        int $maxResults = 50,
        array $fields = []
    ): SearchResult {
        $params = [
            'jql' => $jql,
            'startAt' => $startAt,
            'maxResults' => $maxResults,
        ];

        if ($fields) {
            $params['fields'] = implode(',', $fields);
        }

        $response = $this->request('GET', '/search?' . http_build_query($params));

        $issues = array_map(
            fn(array $issue) => $this->mapIssue($issue),
            $response['issues'] ?? []
        );

        return new SearchResult(
            total: $response['total'] ?? 0,
            startAt: $response['startAt'] ?? 0,
            maxResults: $response['maxResults'] ?? 0,
            issues: $issues,
        );
    }

    public function getIssue(string $key, array $expand = []): Issue
    {
        $params = $expand ? ['expand' => implode(',', $expand)] : [];
        $query = $params ? '?' . http_build_query($params) : '';

        $response = $this->request('GET', "/issue/{$key}{$query}");

        return $this->mapIssue($response, detailed: true);
    }

    public function addComment(string $key, string $body): CommentResult
    {
        $response = $this->request('POST', "/issue/{$key}/comment", [
            'body' => $body,
        ]);

        return new CommentResult(
            id: (string) ($response['id'] ?? ''),
            author: $response['author']['displayName'] ?? 'Unknown',
            body: $response['body'] ?? '',
            created: $response['created'] ?? null,
        );
    }

    public function addWorklog(
        string $key,
        string $timeSpent,
        string $comment = '',
        ?string $started = null
    ): WorklogResult {
        $response = $this->request('POST', "/issue/{$key}/worklog", [
            'timeSpent' => $timeSpent,
            'comment' => $comment,
            'started' => $started ?? date('Y-m-d\TH:i:s.000O'),
        ]);

        return new WorklogResult(
            id: (string) ($response['id'] ?? ''),
            author: $response['author']['displayName'] ?? 'Unknown',
            timeSpent: $response['timeSpent'] ?? '',
            timeSpentSeconds: $response['timeSpentSeconds'] ?? 0,
            started: $response['started'] ?? null,
            comment: $response['comment'] ?? '',
        );
    }

    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->authHeader,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new JiraException("cURL error: {$error}");
        }

        $decoded = json_decode($response, true) ?? [];

        if ($httpCode >= 400) {
            $message = $decoded['errorMessages'][0]
                ?? $decoded['errors'][array_key_first($decoded['errors'] ?? [])]
                ?? "HTTP error {$httpCode}";
            throw new JiraException($message, $httpCode, $decoded);
        }

        return $decoded;
    }

    private function mapIssue(array $data, bool $detailed = false): Issue
    {
        $fields = $data['fields'] ?? [];

        $comments = [];
        $attachments = [];
        $worklogs = [];

        if ($detailed) {
            $comments = array_map(
                fn(array $c) => new Comment(
                    id: (string) ($c['id'] ?? ''),
                    author: $c['author']['displayName'] ?? 'Unknown',
                    authorAvatar: $c['author']['avatarUrls']['48x48'] ?? null,
                    body: $c['body'] ?? '',
                    created: $c['created'] ?? null,
                    updated: $c['updated'] ?? null,
                ),
                $fields['comment']['comments'] ?? []
            );

            $attachments = array_map(
                fn(array $a) => new Attachment(
                    id: (string) ($a['id'] ?? ''),
                    filename: $a['filename'] ?? '',
                    size: $a['size'] ?? 0,
                    mimeType: $a['mimeType'] ?? '',
                    content: $a['content'] ?? '',
                    thumbnail: $a['thumbnail'] ?? null,
                    author: $a['author']['displayName'] ?? 'Unknown',
                    created: $a['created'] ?? null,
                ),
                $fields['attachment'] ?? []
            );

            $worklogs = array_map(
                fn(array $w) => new Worklog(
                    id: (string) ($w['id'] ?? ''),
                    author: $w['author']['displayName'] ?? 'Unknown',
                    authorAvatar: $w['author']['avatarUrls']['48x48'] ?? null,
                    timeSpent: $w['timeSpent'] ?? '',
                    timeSpentSeconds: $w['timeSpentSeconds'] ?? 0,
                    started: $w['started'] ?? null,
                    comment: $w['comment'] ?? '',
                ),
                $fields['worklog']['worklogs'] ?? []
            );
        }

        return new Issue(
            key: $data['key'] ?? '',
            id: (string) ($data['id'] ?? ''),
            summary: $fields['summary'] ?? '',
            status: $fields['status']['name'] ?? 'Unknown',
            statusCategory: $fields['status']['statusCategory']['key'] ?? 'undefined',
            priority: $fields['priority']['name'] ?? 'None',
            assignee: $fields['assignee']['displayName'] ?? 'Unassigned',
            assigneeAvatar: $fields['assignee']['avatarUrls']['48x48'] ?? null,
            reporter: $fields['reporter']['displayName'] ?? 'Unknown',
            created: $fields['created'] ?? null,
            updated: $fields['updated'] ?? null,
            dueDate: $fields['duedate'] ?? null,
            labels: $fields['labels'] ?? [],
            issueType: $fields['issuetype']['name'] ?? 'Task',
            issueTypeIcon: $fields['issuetype']['iconUrl'] ?? null,
            originalEstimate: $fields['timeoriginalestimate'] ?? 0,
            remainingEstimate: $fields['timeestimate'] ?? 0,
            timeSpent: $fields['timespent'] ?? 0,
            originalEstimateFormatted: $fields['timetracking']['originalEstimate'] ?? null,
            remainingEstimateFormatted: $fields['timetracking']['remainingEstimate'] ?? null,
            timeSpentFormatted: $fields['timetracking']['timeSpent'] ?? null,
            description: $detailed ? ($fields['description'] ?? '') : null,
            comments: $comments,
            attachments: $attachments,
            worklogs: $worklogs,
        );
    }
}
