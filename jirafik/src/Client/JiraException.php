<?php

declare(strict_types=1);

namespace Jirafik\Client;

use Exception;

class JiraException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?array $response = null,
    ) {
        parent::__construct($message, $statusCode);
    }
}
