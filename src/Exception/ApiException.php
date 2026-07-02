<?php

declare(strict_types=1);

namespace NexMailPro\PhpSdk\Exception;

final class ApiException extends NexMailProException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly string $responseBody,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function responseBody(): string
    {
        return $this->responseBody;
    }
}
