<?php

declare(strict_types=1);

namespace NexMailPro\PhpSdk\Http;

use NexMailPro\PhpSdk\Exception\TransportException;

final class NativeTransport implements TransportInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function post(string $url, array $headers, string $body, int $timeout): TransportResponse
    {
        if (function_exists('curl_init')) {
            return $this->postWithCurl($url, $headers, $body, $timeout);
        }

        return $this->postWithStreams($url, $headers, $body, $timeout);
    }

    /**
     * @param array<string, string> $headers
     */
    private function postWithCurl(string $url, array $headers, string $body, int $timeout): TransportResponse
    {
        $curl = curl_init($url);

        if ($curl === false) {
            throw new TransportException('Unable to initialize cURL.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_HEADER => true,
        ]);

        $rawResponse = curl_exec($curl);

        if ($rawResponse === false) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new TransportException('cURL request failed: ' . $error);
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        $rawHeaders = substr($rawResponse, 0, $headerSize);
        $responseBody = substr($rawResponse, $headerSize);
        $headerLines = array_values(array_filter(array_map('trim', explode("\r\n", $rawHeaders))));

        return new TransportResponse($statusCode, $responseBody, $headerLines);
    }

    /**
     * @param array<string, string> $headers
     */
    private function postWithStreams(string $url, array $headers, string $body, int $timeout): TransportResponse
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $this->formatHeaders($headers)),
                'content' => $body,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            $error = error_get_last();

            throw new TransportException('Stream request failed: ' . ($error['message'] ?? 'Unknown error.'));
        }

        $responseHeaders = isset($http_response_header) && is_array($http_response_header)
            ? array_values($http_response_header)
            : [];

        return new TransportResponse($this->extractStatusCode($responseHeaders), $responseBody, $responseHeaders);
    }

    /**
     * @param array<string, string> $headers
     *
     * @return list<string>
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];

        foreach ($headers as $name => $value) {
            $formatted[] = sprintf('%s: %s', $name, $value);
        }

        return $formatted;
    }

    /**
     * @param list<string> $headers
     */
    private function extractStatusCode(array $headers): int
    {
        if (! isset($headers[0])) {
            return 0;
        }

        if (preg_match('/\s(\d{3})\s/', $headers[0], $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }
}
