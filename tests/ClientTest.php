<?php

declare(strict_types=1);

namespace NexMailPro\PhpSdk\Tests;

use InvalidArgumentException;
use NexMailPro\PhpSdk\Client;
use NexMailPro\PhpSdk\Exception\ApiException;
use NexMailPro\PhpSdk\Http\TransportInterface;
use NexMailPro\PhpSdk\Http\TransportResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    public function testVerifyEmailBuildsTheExpectedRequest(): void
    {
        $transport = new FakeTransport(new TransportResponse(
            200,
            '{"email":"person@example.com","result":"valid"}',
        ));

        $client = new Client(
            apiKey: 'secret-key',
            transport: $transport,
            headers: ['X-SDK-Source' => 'phpunit'],
        );

        $response = $client->verifyEmail(' person@example.com ', [
            'email' => 'override@example.com',
            'source' => 'signup-form',
        ]);

        self::assertSame('https://nexmailpro.com/api/v1/verify/email', $transport->lastUrl);
        self::assertSame(15, $transport->lastTimeout);
        self::assertSame('application/json', $transport->lastHeaders['Accept']);
        self::assertSame('application/json', $transport->lastHeaders['Content-Type']);
        self::assertSame('Bearer secret-key', $transport->lastHeaders['Authorization']);
        self::assertSame('phpunit', $transport->lastHeaders['X-SDK-Source']);
        self::assertSame([
            'email' => 'person@example.com',
            'source' => 'signup-form',
        ], json_decode($transport->lastBody, true, flags: JSON_THROW_ON_ERROR));
        self::assertSame('valid', $response['result']);
    }

    public function testVerifyEmailThrowsForEmptyEmail(): void
    {
        $client = new Client(transport: new FakeTransport(new TransportResponse(200, '{}')));

        $this->expectException(InvalidArgumentException::class);

        $client->verifyEmail('   ');
    }

    public function testVerifyEmailThrowsForFailedApiResponses(): void
    {
        $client = new Client(transport: new FakeTransport(new TransportResponse(
            422,
            '{"message":"Email is invalid."}',
        )));

        try {
            $client->verifyEmail('person@example.com');
            self::fail('Expected an ApiException to be thrown.');
        } catch (ApiException $exception) {
            self::assertSame(422, $exception->statusCode());
            self::assertSame('{"message":"Email is invalid."}', $exception->responseBody());
        }
    }

    public function testVerifyEmailThrowsForInvalidJsonResponses(): void
    {
        $client = new Client(transport: new FakeTransport(new TransportResponse(200, 'not-json')));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('invalid JSON');

        $client->verifyEmail('person@example.com');
    }
}

final class FakeTransport implements TransportInterface
{
    public string $lastUrl = '';

    /**
     * @var array<string, string>
     */
    public array $lastHeaders = [];

    public string $lastBody = '';

    public int $lastTimeout = 0;

    public function __construct(private readonly TransportResponse $response)
    {
    }

    public function post(string $url, array $headers, string $body, int $timeout): TransportResponse
    {
        $this->lastUrl = $url;
        $this->lastHeaders = $headers;
        $this->lastBody = $body;
        $this->lastTimeout = $timeout;

        return $this->response;
    }
}
