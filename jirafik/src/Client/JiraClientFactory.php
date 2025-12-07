<?php

declare(strict_types=1);

namespace Jirafik\Client;

final class JiraClientFactory
{
    /**
     * Create Jira client from environment variables
     */
    public static function fromEnv(): JiraClientInterface
    {
        return NativeJiraClient::fromEnv();
    }
}
