<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Jirafik\Client\JiraClientInterface;
use Jirafik\Client\JiraClientFactory;
use Jirafik\Client\JiraException;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================
// Helper Functions
// ============================================

function jsonResponse(array $data, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function formatSeconds(int $seconds): string
{
    if ($seconds === 0) {
        return '0m';
    }

    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    return match (true) {
        $hours > 0 && $minutes > 0 => "{$hours}h {$minutes}m",
        $hours > 0 => "{$hours}h",
        default => "{$minutes}m",
    };
}

function getInput(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ============================================
// Action Handlers
// ============================================

/**
 * Search issues with JQL
 * GET ?action=search&jql=project=XXX&maxResults=50&startAt=0
 */
function actionSearch(JiraClientInterface $client): never
{
    $jql = $_GET['jql'] ?? 'ORDER BY updated DESC';
    $maxResults = (int) ($_GET['maxResults'] ?? 50);
    $startAt = (int) ($_GET['startAt'] ?? 0);

    $result = $client->search($jql, $startAt, $maxResults, [
        'summary', 'status', 'priority', 'assignee', 'reporter',
        'created', 'updated', 'duedate', 'labels', 'issuetype',
        'timeoriginalestimate', 'timeestimate', 'timespent', 'timetracking'
    ]);

    $issues = array_map(fn($issue) => $issue->toArray(), $result->issues);

    jsonResponse([
        'success' => true,
        'total' => $result->total,
        'startAt' => $result->startAt,
        'maxResults' => $result->maxResults,
        'issues' => $issues,
    ]);
}

/**
 * Get single issue with full details
 * GET ?action=get&key=XXX-123
 */
function actionGet(JiraClientInterface $client): never
{
    $key = $_GET['key'] ?? '';

    if ($key === '') {
        jsonResponse(['success' => false, 'error' => 'Issue key is required'], 400);
    }

    $issue = $client->getIssue($key, ['renderedFields', 'changelog']);

    jsonResponse([
        'success' => true,
        'issue' => $issue->toArray(detailed: true),
    ]);
}

/**
 * Get time tracking report for issues
 * GET ?action=timeReport&jql=project=XXX&maxResults=100
 */
function actionTimeReport(JiraClientInterface $client): never
{
    $jql = $_GET['jql'] ?? 'ORDER BY updated DESC';
    $maxResults = (int) ($_GET['maxResults'] ?? 100);

    $result = $client->search($jql, 0, $maxResults, [
        'summary', 'status', 'assignee', 'issuetype',
        'timeoriginalestimate', 'timeestimate', 'timespent', 'timetracking', 'worklog'
    ]);

    $issues = array_map(fn($issue) => $issue->toArray(), $result->issues);

    $totalOriginalEstimate = array_sum(array_column($issues, 'originalEstimate'));
    $totalRemainingEstimate = array_sum(array_column($issues, 'remainingEstimate'));
    $totalTimeSpent = array_sum(array_column($issues, 'timeSpent'));

    jsonResponse([
        'success' => true,
        'report' => [
            'totalOriginalEstimate' => $totalOriginalEstimate,
            'totalRemainingEstimate' => $totalRemainingEstimate,
            'totalTimeSpent' => $totalTimeSpent,
            'totalOriginalEstimateFormatted' => formatSeconds($totalOriginalEstimate),
            'totalRemainingEstimateFormatted' => formatSeconds($totalRemainingEstimate),
            'totalTimeSpentFormatted' => formatSeconds($totalTimeSpent),
            'issues' => $issues,
        ],
    ]);
}

/**
 * Add comment to issue
 * POST ?action=addComment  body: {key: "XXX-123", comment: "text"}
 */
function actionAddComment(JiraClientInterface $client): never
{
    $input = getInput();
    $key = $input['key'] ?? '';
    $commentText = $input['comment'] ?? '';

    if ($key === '' || $commentText === '') {
        jsonResponse(['success' => false, 'error' => 'Issue key and comment are required'], 400);
    }

    $result = $client->addComment($key, $commentText);

    jsonResponse([
        'success' => true,
        'comment' => $result->toArray(),
    ]);
}

/**
 * Add worklog to issue
 * POST ?action=addWorklog  body: {key: "XXX-123", timeSpent: "2h", comment: "text", started: "2024-01-01T10:00:00.000+0000"}
 */
function actionAddWorklog(JiraClientInterface $client): never
{
    $input = getInput();
    $key = $input['key'] ?? '';
    $timeSpent = $input['timeSpent'] ?? '';
    $comment = $input['comment'] ?? '';
    $started = $input['started'] ?? null;

    if ($key === '' || $timeSpent === '') {
        jsonResponse(['success' => false, 'error' => 'Issue key and timeSpent are required'], 400);
    }

    $result = $client->addWorklog($key, $timeSpent, $comment, $started);

    jsonResponse([
        'success' => true,
        'worklog' => $result->toArray(),
    ]);
}

/**
 * Handle unknown action
 */
function actionUnknown(): never
{
    jsonResponse([
        'success' => false,
        'error' => 'Unknown action',
        'availableActions' => ['search', 'get', 'timeReport', 'addComment', 'addWorklog'],
    ], 400);
}

// ============================================
// Main Router
// ============================================

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Create client from environment (JIRA_CLIENT_TYPE: 'native')
    $client = JiraClientFactory::fromEnv();

    $handler = match ($action) {
        'search' => actionSearch(...),
        'get' => actionGet(...),
        'timeReport' => actionTimeReport(...),
        'addComment' => actionAddComment(...),
        'addWorklog' => actionAddWorklog(...),
        default => fn(JiraClientInterface $_) => actionUnknown(),
    };

    $handler($client);

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
