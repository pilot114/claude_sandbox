<?php

require_once __DIR__ . '/vendor/autoload.php';

use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Comment;
use JiraRestApi\JiraException;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Simple JSON response helper
 */
function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Parse issue data to simplified format
 */
function parseIssue(object $issue, bool $detailed = false): array
{
    $fields = $issue->fields;

    $result = [
        'key' => $issue->key,
        'id' => $issue->id,
        'summary' => $fields->summary ?? '',
        'status' => $fields->status->name ?? 'Unknown',
        'statusCategory' => $fields->status->statusCategory->key ?? 'undefined',
        'priority' => $fields->priority->name ?? 'None',
        'assignee' => $fields->assignee->displayName ?? 'Unassigned',
        'assigneeAvatar' => $fields->assignee->avatarUrls->{'48x48'} ?? null,
        'reporter' => $fields->reporter->displayName ?? 'Unknown',
        'created' => $fields->created ?? null,
        'updated' => $fields->updated ?? null,
        'dueDate' => $fields->duedate ?? null,
        'labels' => $fields->labels ?? [],
        'issueType' => $fields->issuetype->name ?? 'Task',
        'issueTypeIcon' => $fields->issuetype->iconUrl ?? null,
        // Time tracking
        'originalEstimate' => $fields->timeoriginalestimate ?? 0,
        'remainingEstimate' => $fields->timeestimate ?? 0,
        'timeSpent' => $fields->timespent ?? 0,
        'originalEstimateFormatted' => $fields->timetracking->originalEstimate ?? null,
        'remainingEstimateFormatted' => $fields->timetracking->remainingEstimate ?? null,
        'timeSpentFormatted' => $fields->timetracking->timeSpent ?? null,
    ];

    if ($detailed) {
        $result['description'] = $fields->description ?? '';

        // Comments
        $result['comments'] = [];
        if (isset($fields->comment->comments)) {
            foreach ($fields->comment->comments as $comment) {
                $result['comments'][] = [
                    'id' => $comment->id,
                    'author' => $comment->author->displayName ?? 'Unknown',
                    'authorAvatar' => $comment->author->avatarUrls->{'48x48'} ?? null,
                    'body' => $comment->body ?? '',
                    'created' => $comment->created ?? null,
                    'updated' => $comment->updated ?? null,
                ];
            }
        }

        // Attachments
        $result['attachments'] = [];
        if (isset($fields->attachment)) {
            foreach ($fields->attachment as $attachment) {
                $result['attachments'][] = [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'size' => $attachment->size,
                    'mimeType' => $attachment->mimeType,
                    'content' => $attachment->content,
                    'thumbnail' => $attachment->thumbnail ?? null,
                    'author' => $attachment->author->displayName ?? 'Unknown',
                    'created' => $attachment->created ?? null,
                ];
            }
        }

        // Worklogs (time entries)
        $result['worklogs'] = [];
        if (isset($fields->worklog->worklogs)) {
            foreach ($fields->worklog->worklogs as $worklog) {
                $result['worklogs'][] = [
                    'id' => $worklog->id,
                    'author' => $worklog->author->displayName ?? 'Unknown',
                    'authorAvatar' => $worklog->author->avatarUrls->{'48x48'} ?? null,
                    'timeSpent' => $worklog->timeSpent ?? '',
                    'timeSpentSeconds' => $worklog->timeSpentSeconds ?? 0,
                    'started' => $worklog->started ?? null,
                    'comment' => $worklog->comment ?? '',
                ];
            }
        }
    }

    return $result;
}

/**
 * Get request action
 */
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $issueService = new IssueService();

    switch ($action) {
        /**
         * Search issues with JQL
         * GET ?action=search&jql=project=XXX&maxResults=50&startAt=0
         */
        case 'search':
            $jql = $_GET['jql'] ?? 'ORDER BY updated DESC';
            $maxResults = (int) ($_GET['maxResults'] ?? 50);
            $startAt = (int) ($_GET['startAt'] ?? 0);

            $result = $issueService->search($jql, $startAt, $maxResults, [
                'summary', 'status', 'priority', 'assignee', 'reporter',
                'created', 'updated', 'duedate', 'labels', 'issuetype',
                'timeoriginalestimate', 'timeestimate', 'timespent', 'timetracking'
            ]);

            $issues = [];
            foreach ($result->issues as $issue) {
                $issues[] = parseIssue($issue);
            }

            jsonResponse([
                'success' => true,
                'total' => $result->total,
                'startAt' => $result->startAt,
                'maxResults' => $result->maxResults,
                'issues' => $issues,
            ]);
            break;

        /**
         * Get single issue with full details
         * GET ?action=get&key=XXX-123
         */
        case 'get':
            $key = $_GET['key'] ?? '';
            if (empty($key)) {
                jsonResponse(['success' => false, 'error' => 'Issue key is required'], 400);
            }

            $issue = $issueService->get($key, [
                'expand' => 'renderedFields,changelog'
            ]);

            jsonResponse([
                'success' => true,
                'issue' => parseIssue($issue, true),
            ]);
            break;

        /**
         * Get time tracking report for issues
         * GET ?action=timeReport&jql=project=XXX&maxResults=100
         */
        case 'timeReport':
            $jql = $_GET['jql'] ?? 'ORDER BY updated DESC';
            $maxResults = (int) ($_GET['maxResults'] ?? 100);

            $result = $issueService->search($jql, 0, $maxResults, [
                'summary', 'status', 'assignee', 'issuetype',
                'timeoriginalestimate', 'timeestimate', 'timespent', 'timetracking', 'worklog'
            ]);

            $report = [
                'totalOriginalEstimate' => 0,
                'totalRemainingEstimate' => 0,
                'totalTimeSpent' => 0,
                'issues' => [],
            ];

            foreach ($result->issues as $issue) {
                $parsed = parseIssue($issue);
                $report['totalOriginalEstimate'] += $parsed['originalEstimate'];
                $report['totalRemainingEstimate'] += $parsed['remainingEstimate'];
                $report['totalTimeSpent'] += $parsed['timeSpent'];
                $report['issues'][] = $parsed;
            }

            // Format totals
            $report['totalOriginalEstimateFormatted'] = formatSeconds($report['totalOriginalEstimate']);
            $report['totalRemainingEstimateFormatted'] = formatSeconds($report['totalRemainingEstimate']);
            $report['totalTimeSpentFormatted'] = formatSeconds($report['totalTimeSpent']);

            jsonResponse([
                'success' => true,
                'report' => $report,
            ]);
            break;

        /**
         * Add comment to issue
         * POST ?action=addComment  body: {key: "XXX-123", comment: "text"}
         */
        case 'addComment':
            $input = json_decode(file_get_contents('php://input'), true);
            $key = $input['key'] ?? '';
            $commentText = $input['comment'] ?? '';

            if (empty($key) || empty($commentText)) {
                jsonResponse(['success' => false, 'error' => 'Issue key and comment are required'], 400);
            }

            $comment = new Comment();
            $comment->setBody($commentText);

            $result = $issueService->addComment($key, $comment);

            jsonResponse([
                'success' => true,
                'comment' => [
                    'id' => $result->id,
                    'author' => $result->author->displayName ?? 'Unknown',
                    'body' => $result->body,
                    'created' => $result->created,
                ],
            ]);
            break;

        /**
         * Add worklog to issue
         * POST ?action=addWorklog  body: {key: "XXX-123", timeSpent: "2h", comment: "text", started: "2024-01-01T10:00:00.000+0000"}
         */
        case 'addWorklog':
            $input = json_decode(file_get_contents('php://input'), true);
            $key = $input['key'] ?? '';
            $timeSpent = $input['timeSpent'] ?? '';
            $comment = $input['comment'] ?? '';
            $started = $input['started'] ?? date('Y-m-d\TH:i:s.000O');

            if (empty($key) || empty($timeSpent)) {
                jsonResponse(['success' => false, 'error' => 'Issue key and timeSpent are required'], 400);
            }

            $worklog = new \JiraRestApi\Issue\Worklog();
            $worklog->setComment($comment)
                    ->setStarted($started)
                    ->setTimeSpent($timeSpent);

            $result = $issueService->addWorklog($key, $worklog);

            jsonResponse([
                'success' => true,
                'worklog' => [
                    'id' => $result->id,
                    'author' => $result->author->displayName ?? 'Unknown',
                    'timeSpent' => $result->timeSpent,
                    'timeSpentSeconds' => $result->timeSpentSeconds,
                    'started' => $result->started,
                    'comment' => $result->comment ?? '',
                ],
            ]);
            break;

        default:
            jsonResponse([
                'success' => false,
                'error' => 'Unknown action',
                'availableActions' => ['search', 'get', 'timeReport', 'addComment', 'addWorklog'],
            ], 400);
    }

} catch (JiraException $e) {
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}

/**
 * Format seconds to human readable string
 */
function formatSeconds(int $seconds): string
{
    if ($seconds === 0) {
        return '0m';
    }

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    $result = '';
    if ($hours > 0) {
        $result .= $hours . 'h ';
    }
    if ($minutes > 0 || $hours === 0) {
        $result .= $minutes . 'm';
    }

    return trim($result);
}
