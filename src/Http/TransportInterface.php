<?php

declare(strict_types=1);

namespace NexMailPro\PhpSdk\Http;

interface TransportInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function post(string $url, array $headers, string $body, int $timeout): TransportResponse;
}
