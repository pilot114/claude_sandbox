<?php

declare(strict_types=1);

namespace Jirafik\Client;

enum ClientType: string
{
    case Native = 'native';
    case Lesstif = 'lesstif';
}

final class JiraClientFactory
{
    /**
     * Create Jira client based on type
     *
     * @param ClientType|string $type Client type ('native' or 'lesstif')
     */
    public static function create(ClientType|string $type = ClientType::Native): JiraClientInterface
    {
        $clientType = $type instanceof ClientType ? $type : ClientType::tryFrom($type);

        return match ($clientType) {
            ClientType::Native => NativeJiraClient::fromEnv(),
            ClientType::Lesstif => new LesstifJiraClient(),
            default => throw new JiraException("Unknown client type: {$type}"),
        };
    }

    /**
     * Create client from environment variable JIRA_CLIENT_TYPE
     * Defaults to 'native' if not set
     */
    public static function fromEnv(): JiraClientInterface
    {
        $type = $_ENV['JIRA_CLIENT_TYPE'] ?? getenv('JIRA_CLIENT_TYPE') ?: 'native';

        return self::create($type);
    }
}
