<?php

declare(strict_types=1);

namespace NexMailPro\PhpSdk;

use InvalidArgumentException;
use NexMailPro\PhpSdk\Exception\ApiException;
use NexMailPro\PhpSdk\Exception\NexMailProException;
use NexMailPro\PhpSdk\Http\NativeTransport;
use NexMailPro\PhpSdk\Http\TransportInterface;

final class Client
{
    public const DEFAULT_BASE_URL = 'https://nexmailpro.com';
    public const VERIFY_EMAIL_PATH = '/api/v1/verify/email';
    public const USER_AGENT = 'nexmailpro-php-sdk/0.1.0';

    private readonly TransportInterface $transport;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
        private readonly int $timeout = 15,
        ?TransportInterface $transport = null,
        private readonly array $headers = [],
    ) {
        if ($this->timeout < 1) {
            throw new InvalidArgumentException('Timeout must be at least 1 second.');
        }

        $this->transport = $transport ?? new NativeTransport();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function verifyEmail(string $email, array $payload = []): array
    {
        $email = trim($email);

        if ($email === '') {
            throw new InvalidArgumentException('Email must not be empty.');
        }

        $payload['email'] = $email;

        return $this->postJson(self::VERIFY_EMAIL_PATH, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function postJson(string $path, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            throw new NexMailProException('Failed to encode request payload to JSON.');
        }

        $response = $this->transport->post(
            $this->buildUrl($path),
            $this->buildHeaders(),
            $body,
            $this->timeout,
        );

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            throw new ApiException(
                sprintf('NexMailPro API request failed with status %d.', $response->statusCode),
                $response->statusCode,
                $response->body,
            );
        }

        if ($response->body === '') {
            return [];
        }

        $decoded = json_decode($response->body, true);

        if (! is_array($decoded)) {
            throw new ApiException(
                'NexMailPro API returned an invalid JSON response.',
                $response->statusCode,
                $response->body,
            );
        }

        return $decoded;
    }

    private function buildUrl(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => self::USER_AGENT,
        ];

        if ($this->apiKey !== null && $this->apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        foreach ($this->headers as $name => $value) {
            $headers[$name] = $value;
        }

        return $headers;
    }
}
